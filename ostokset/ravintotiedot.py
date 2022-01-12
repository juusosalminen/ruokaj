import datetime
import json
import sys

import matplotlib.pyplot as plt
import pandas as pd

import ravintolasku
from tunnushaku import tunnushaku

KUVIEN_POLKU = tunnushaku('polut')['kuvat']
RAJA_ARVOJEN_POLKU = tunnushaku('polut')['raja_arvot']

with open(RAJA_ARVOJEN_POLKU,'r') as f:
    raja_arvot = json.load(f)        


def prh_ka():
    tiedot = ravintolasku.main()
    ka_tiedot=tiedot.mean()

    tavoite_dict = {'rasva_g' : [37,[0.25,0.4]],
                    'hiilihydraatti_imeytyvä_g' : [17,[0.45,0.6]],
                    'proteiini_g' : [17,[0.1, 0.2]]
        }

    for ravinne in tavoite_dict.keys():
        alaraja = ka_tiedot['energia_kJ']*tavoite_dict[ravinne][1][0]
        ylaraja = ka_tiedot['energia_kJ']*tavoite_dict[ravinne][1][1]
        toteutunut = ka_tiedot[ravinne]*tavoite_dict[ravinne][0]
        if  alaraja < toteutunut < ylaraja:
            print(f'{ravinne} hyvä')
        print(f"{ravinne} : {toteutunut/ka_tiedot['energia_kJ']}")


    ka_tiedot[tavoite_dict.keys()].plot.pie()
    plt.show()



def rasvatiedot():
    tiedot = ravintolasku.main()
    ka_tiedot=tiedot.mean()
    rasvadict={'rasvahapot_monityydyttymättömät_g' : [0.05,0.1],
               'rasvahapot_n_3_monityydyttymättömät_g' : [0.01,1],
               'rasvahapot_yksittäistyydyttymättömät_cis_g': [0.1, 0.2],
               #'rasvahappo_alfalinoleenihappo_mg' : [0.005,1],
               'rasvahapot_tyydyttyneet_g' : [0,0.1]}

    alarajat = {#'rasvahappo_linolihappo_mg' : 0.03,
                'rasvahappo_DHA_mg' : 200,
                'rasvahapot_trans_g' : 0}
    tarkasteltavat=list(rasvadict.keys())+list(alarajat.keys())


    varit=[]
    for ravinne in tarkasteltavat:
        if 'DHA' in ravinne:
            arvo = ka_tiedot[ravinne]
        elif 'mg' in ravinne:
            arvo = ka_tiedot[ravinne]/1000*37/ka_tiedot['energia_kJ']    #E%
        else:
            arvo = ka_tiedot[ravinne]*37/ka_tiedot['energia_kJ']
        if ravinne in rasvadict.keys():
            alaraja = rasvadict[ravinne][0]
            ylaraja = rasvadict[ravinne][1]
            if arvo < alaraja or arvo > ylaraja:
                varit.append('r')
            else:
                varit.append('g')
        elif ravinne in alarajat.keys():
            alaraja = alarajat[ravinne]
            if arvo < alaraja:
                varit.append('r')
            else:
                varit.append('g')
        print(f'Ravinteen {ravinne} arvo on {round(arvo,3)} suositus on {alaraja} ja {ylaraja} välillä')

    ka_tiedot[tarkasteltavat].plot.barh(color=varit)
    plt.show()



def vk_ka_roll(ravinne):
    '''Viikoittainen liukuva keskiarvo koko tarkasteluajalta'''
    tiedot = ravintolasku.kaikki()

    if ravinne in tiedot.columns:
        roll_ka = tiedot[ravinne].rolling(7).mean()
    else:
        print('Tuntematon ravinne')
    if ravinne == 'energia_kJ':
        roll_ka *= 0.23
    


    roll_ka.where(roll_ka < 1, roll_ka.mean())
    fig, axes = plt.subplots(1,1)
    fig = plt.gcf()
    fig.set_size_inches(18.5, 10.5)

    roll_ka.plot(ax=axes)

    if ravinne in raja_arvot.keys():
        raja = raja_arvot[ravinne]
        axes.axhline(raja, color='r', ls='--')

    plt.savefig(KUVIEN_POLKU + 'vk_ka_roll.png', bbox_inches='tight')

def ravinnepylvaat(ravinne, lkm, tavoite=True):
    """Pylväsdiagrammi ravinteista päivittäin

    Args:
        ravinne (str): Tarkasteltava ravinne
        lkm (str/int): Kuinka monta pylvästä/päivää
        tavoite (bool, optional): Piirretäänkö tavoiteviiva. Defaults to True.
    """    

    tanaan = datetime.datetime.today()
    ero = datetime.timedelta(days=int(lkm) - 1)
    alku = tanaan - ero
    df = ravintolasku.usea([alku.strftime('%Y-%m-%d'), tanaan.strftime('%Y-%m-%d')])

    fig, axes = plt.subplots(1,1)
    fig = plt.gcf()
    fig.set_size_inches(10.5, 8.5)
    axes.set_title(f'{ravinne} edellisen {lkm} päivän ajalta'.capitalize().replace('_',' '))

    df['energia_kJ'] *= 0.23
    df[ravinne].plot(ax=axes, kind='bar')

    #linkkien asettaminen
    url = f'/ruoka/merkinnat/tarkastelu/kasittely.php?funktio=paivan_ravinteet&ravinne={ravinne}&jarjesta=0&pvm='
    for i, child in enumerate(axes.containers[0].get_children()):
        child.set_url(url + df.index[i])


    if ravinne in raja_arvot.keys():
        raja = int(raja_arvot[ravinne])
        line = axes.axhline(raja, color='r', ls='--')
        axes.legend([line], [f'Tavoite: {raja}'])
    
    plt.savefig(KUVIEN_POLKU + 'ravinnepylvaat.svg', bbox_inches='tight')

if __name__ == '__main__':
    if sys.argv[1] == 'vk_ka_roll':
        vk_ka_roll(sys.argv[2])
    if sys.argv[1] == 'ravinnepylvaat':
        ravinnepylvaat(sys.argv[2], sys.argv[3])






