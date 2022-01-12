import argparse
import datetime as dt
import statistics
import sys
from calendar import monthrange
from datetime import datetime

import piexif
import piexif.helper
import matplotlib.pyplot as plt
import numpy as np
import pandas as pd
from scipy import integrate, stats

import ravintolasku
import sql_yhteys
from tunnushaku import tunnushaku

KUVIEN_POLKU = tunnushaku('polut')['kuvat']
DATE_FORMAT = '%Y-%m-%d'

def laskettavat_kulut():
    """
    Laskettavien tuotteiden kulujen kohdistus kulutuksen mukaan,
    tarkempi kuin pvm mukainen

    Returns:
        DataFrame: Tarkemmat kulut tuotteittain ja päivittäin
    """
    valitsin = sql_yhteys.yhteys()
    haku = '''       
        SELECT DISTINCT t.nimi, r.maara,r.pvm, o1.hinta, o1.maara, o1.pvm_alku, o1.pvm_loppu, r.ruoka_id
        FROM tuote t
        JOIN ruokailu r USING(tuote_id)
        JOIN ostos o1 USING(tuote_id)
        WHERE t.laskettava = 1
        AND o1.maara IS NOT NULL
        AND r.pvm BETWEEN o1.pvm_alku AND o1.pvm_loppu
        AND o1.pvm_alku = (
            SELECT min(pvm_alku)
            FROM ostos o2 
            WHERE r.pvm BETWEEN o2.pvm_alku AND o2.pvm_loppu
            AND r.tuote_id = o2.tuote_id
            )
    '''

    valitsin.execute(haku)

    tulokset = valitsin.fetchall()
    kulut = {}
    j = 1
    lisakulut = {}
    kulutettu = 0
    for rivi in tulokset:
        pvm = rivi[2].strftime('%Y-%m-%d')
        tuote = rivi[0]
        kulu = round(float(rivi[3].replace(',', '.'))
                     * int(rivi[1])/int(rivi[4]), 5)
        if tuote in kulut.keys():
            kulut[tuote].append((pvm, kulu))
        else:
            kulut[tuote] = [(pvm, kulu)]
        # jos merkittyä kulutusta vähemmän kuin ostettu

        if rivi[6] == tulokset[j-1][6] and rivi[5] == tulokset[j-1][5]:
            ostospaino = int(tulokset[j-1][4])
            kulutettu += int(tulokset[j-1][1])

        elif tulokset[j-1][6] == tulokset[j-2][6] and tulokset[j-1][5] == tulokset[j-2][5]:
            ostospaino = int(tulokset[j-1][4])
            kulutettu += int(tulokset[j-1][1])

            if ostospaino-kulutettu > 5:
                # erotus jaetaan päivien kesken
                paivaero = tulokset[j-1][6]-tulokset[j-1][5]
                lisattava = (ostospaino-kulutettu)/paivaero.days
                hintalisa = lisattava / \
                    int(tulokset[j-1][4]) * \
                    float(tulokset[j-1][3].replace(',', '.'))
                paivat = pd.date_range(tulokset[j-1][5], tulokset[j-1][6])
                for i in range(paivaero.days):
                    if tulokset[j-1][0] in lisakulut.keys():
                        lisakulut[tulokset[j-1][0]
                                  ].append((paivat[i].strftime('%Y-%m-%d'), hintalisa))
                    else:
                        lisakulut[tulokset[j-1][0]
                                  ] = [(paivat[i].strftime('%Y-%m-%d'), hintalisa)]
            kulutettu = 0
        j += 1

    alku = '2021-02-07'

    index = pd.date_range(alku, datetime.now())
    df = pd.DataFrame(index=index, columns=kulut.keys())
    lk_df = pd.DataFrame(index=index, columns=kulut.keys())

    for i in range(len(lisakulut.keys())):
        avain = list(lisakulut.keys())[i]
        for j in range(len(lisakulut[avain])):
            lk_df[avain][lisakulut[avain][j][0]] = lisakulut[avain][j][1]

    for i in range(len(kulut.keys())):
        avain = list(kulut.keys())[i]
        for j in range(len(kulut[avain])):
            df[avain][kulut[avain][j][0]] = kulut[avain][j][1]
    df = df.add(lk_df, fill_value=0)
    df.fillna(0, inplace=True)

    sql_yhteys.sulje_yhteys(valitsin)
    return df


def tuotehaku():
    '''Hakee kaikki tuotteet tietokannasta

    Returns:
        list: Tuotteet listassa
    '''
    valitsin = sql_yhteys.yhteys()

    hakulause = 'SELECT nimi FROM tuote'

    valitsin.execute(hakulause)
    tulokset = valitsin.fetchall()

    tuotteet = []
    for kohde in tulokset:
        tuotteet.append(kohde[0])

    sql_yhteys.sulje_yhteys(valitsin)

    return tuotteet


def kululasku():
    '''
    Ostosten kulujen laskeminen tietokannasta. 
    Kustannukset jaetaan tasaisesti alku- ja loppupäivämäärän väliin,
    ellei erikseen määritetty kulutuksen mukaista kohdistusta.

    Returns:
        DataFrame: Lasketutu kulut tuotteittain ja päivittäin
    '''
    tuotteet = tuotehaku()
    valitsin = sql_yhteys.yhteys()

    ostoshaku = '''SELECT hinta, pvm_alku, pvm_loppu, nimi
                   FROM ostos
                   JOIN tuote
                   USING(tuote_id)
                   ORDER BY pvm_alku
                   '''

    valitsin.execute(ostoshaku)

    ostokset = valitsin.fetchall()

    alkupaiva = ostokset[0][1]
    tanaan = datetime.today()
    paivat = pd.date_range(alkupaiva, tanaan)
    data = np.zeros((paivat.size, len(tuotteet)))

    for hinta, pvm_alku, pvm_loppu, tuote in ostokset:
        if pd.notnull(pvm_loppu):
            delta = pvm_loppu - pvm_alku

            arvo = float(hinta.replace(',', '.')) / (delta.days + 1)
            for x in range(len(tuotteet)):
                if tuote == tuotteet[x]:
                    for i in range(delta.days + 1):
                        ero = pvm_alku - alkupaiva
                        data[:, x][ero.days + i] += arvo

    df = pd.DataFrame(index=paivat, columns=tuotteet, data=data)

    # Tarkempien tietojen päivitys
    tarkemmat = laskettavat_kulut()
    df.update(tarkemmat)

    sql_yhteys.sulje_yhteys(valitsin)
    return df



def paivasumma(vuosi, kk):
    '''Kuukauden kulut päivittäisinä pylväinä

    Args:
        vuosi (int): Tarkasteltavan kuukauden vuosi (YYYY)
        kk (int): Tarkasteltava kuukausi (MM)
    '''    
    df = kululasku()

    paivien_lkm = monthrange(int(vuosi), int(kk))[1]
    alkupvm = f'{vuosi}-{kk}-01'
    loppupvm = (datetime.strptime(alkupvm, DATE_FORMAT) + dt.timedelta(days=paivien_lkm - 1)).strftime(DATE_FORMAT) 
    
    paivasumma_df = df[alkupvm : loppupvm].sum(axis=1)

    # Kuvaajan luonti
    fig, axes = plt.subplots(1,1)
    fig.set_size_inches(12, 5)
    paivasumma_df.plot(stacked=True, ax=axes, kind='bar')

    # x-akselin muotoilu
    x_labels = paivasumma_df.index.strftime('%d')
    axes.set_xticklabels(x_labels, rotation=0)

    # Keskiarvoviiva
    keskiarvo = round(paivasumma_df.mean(), 2)
    line = axes.axhline(keskiarvo, color='r', ls='--')
    axes.legend([line], [f'Keskiarvo: {keskiarvo}'])

    # Otsikko
    axes.set_title(f'Toteutuneet kulut {kk}/{vuosi}')

    # Linkkien asettaminen
    url = f'/ruoka/merkinnat/tarkastelu/kasittely.php?funktio=paivien_kulut&pvm={vuosi}-{kk}-'
    for i, child in enumerate(axes.containers[0].get_children()):
        child.set_url(url + paivasumma_df.index[i].strftime('%d'))

    # Kuvan tallennus
    plt.savefig(KUVIEN_POLKU + 'paivasumma.svg', bbox_inches='tight')

def tuotehinta_tod_nak(tuote, piste, df=kululasku(), piirra=False):
    """Laskee todennäköisyyden pienemmälle arvolle kuin piste

    Args:
        tuote (str): Tarkasteltava tuote
        piste (str/float): Tarkasteltava hinta
        df (DataFrame): Kulut kehikossa. Jos ei annettu niin laskee itse (hidasta)
        piirra (bool): Piirretäänkö kuvaaja, oletuksena ei

    Returns:
        float: Todennäköisyys pienemmälle hinnalle kuin piste
        kolmen desimaalin tarkkuudella
    """
    # Tyyppimuunnos varmuuden vuoksi
    piste = float(piste)

    # Tuotteen rajaus ja nollat pois
    df = df[tuote].replace(0, np.nan).dropna()
    
    # Tehdään ydinestimointi (Kernel Density Estimation) tiheysfunktion saamiseksi
    try:
        kde = stats.gaussian_kde(df)
    except np.linalg.LinAlgError:  # Jos kaikki arvot samoja (singular matrix)
        return np.nan

    legend = 'Ydinestimoitu hajonta (KDE)'
    # Jos ennustaa alle nollan niin tehdään log-muunnos ja palautus
    if kde(0) > 0.05:
        legend = legend[:-1] + ' + log-muunnos)'
        pseudo_kde = stats.gaussian_kde(np.log(df))
        kde = lambda a : pseudo_kde(np.log(a))/a
    # Integroidaan saatu funktio haluttuun pisteeseen (= saadaan pienemmän arvon tn)
    tod = integrate.quad(kde, 0, piste)[0]

    # Visualisointi
    if piirra:
        # Histogrammi, otsikko ja akselien selitteet
        fig, ax = plt.subplots()
        df.plot.hist(alpha=0.6, label='Toteutuneet frekvenssit', ax=ax, bins='fd')
        ax.set_title(tuote)
        ax.set_xlabel('Kulut/päivä (e)')
        ax.set_ylabel('Frekvenssi')

        # Toinen y-akseli samaan kuvaan
        ax2 = ax.twinx()
        ax2.set_ylabel('Arvioitu tiheys')
        
        # KDE piirto ja maalaus viivan alle pisteeseen saakka
        ylaraja = df.max() * 1.25
        x = np.linspace(0.00001, ylaraja, 1000)
        ax2.plot(x, kde(x), color='r', label=legend)
        ax2.fill_between(x, kde(x), where=x<piste, alpha=0.3,
                        color='r', label='Pienemmän arvon todennäköisyys')

        # Pystyviiva tarkastelukohtaan kuvaajan saakka
        ax2.vlines(x=piste, ymin=0, ymax=kde(piste), color='r', ls='--', label='Tarkasteltava piste')
        
        # Y-akselien tasaus ja kuvaajan selitteet
        ax2.set_ylim(bottom=0)
        fig.legend(bbox_to_anchor=(1,1), bbox_transform=ax.transAxes)
        
        fig.set_size_inches(10,6)
        kuvapolku = KUVIEN_POLKU + f'hist_tod_nak_{tuote}.jpeg'
        plt.savefig(kuvapolku, bbox_inches='tight')

        kommentti = piexif.helper.UserComment.dump(str(piste))
        exif_dict = piexif.load(kuvapolku)
        exif_dict['Exif'][piexif.ExifIFD.UserComment] = kommentti
        exif_bytes = piexif.dump(exif_dict)
        piexif.insert(exif_bytes, kuvapolku)
        
    return round(tod, 3)

def paivien_kulut(pvm):
    """Tulostaa html-taulukkona tietyn päivän kulut tuotteittain

    Args:
        pvm (str): Tarkasteltava päivä
    """
    df = kululasku()
    paivan_kulut = df.loc[pvm]

    # Nollat pois
    paivan_kulut = paivan_kulut[paivan_kulut != 0].to_frame()

    # Laskeva järjestys hinnan mukaan
    paivan_kulut.sort_values(by=pvm, ascending=False, inplace=True)

    # Sarakkeen nimeäminen
    paivan_kulut.columns = ['Hinta']

    # Todennäköisyys pienemmästä arvosta
    todennakoisyydet = []
    for tuote in paivan_kulut.index:
        hinta = paivan_kulut.loc[tuote]['Hinta']
        tn = tuotehinta_tod_nak(tuote, hinta, df)
        tn_linkki = f'<a href=/ruoka/merkinnat/tarkastelu/tuoteraportti.php?tuote={tuote}&piste={hinta}>{tn}</a>'
        todennakoisyydet.append(tn_linkki)
    paivan_kulut['TN'] = todennakoisyydet

    linkki = '<a href=/ruoka/merkinnat/tarkastelu/tuoteraportti.php?tuote='
    paivan_kulut.index = paivan_kulut.index.map(lambda x: linkki + x + f'>{x}</a>')

    # Tulostus
    print(paivan_kulut.to_html(border=0, render_links=True, escape=False))


def keskim(haettava='kaikki', tulosta=True):
    # keskimääräinen kestoaika ja päivähinta
    df = kululasku()
    def haku(tuote):
        if len(df[tuote][df[tuote] != 0]) > 0:
            keskihinta = round(df[tuote][df[tuote] != 0].mean(), 2)

            valitsin = sql_yhteys.yhteys()

            sql = f'''SELECT pvm_alku, pvm_loppu
                        FROM ostos
                        WHERE tuote_id = (
                            SELECT tuote_id
                            FROM tuote
                            WHERE nimi='{tuote}'
                        )'''

            valitsin.execute(sql)
            tulokset = valitsin.fetchall()
            putkilista = []
            for pvm_alku, pvm_loppu in tulokset:
                if pd.notnull(pvm_loppu):
                    ero = pvm_loppu - pvm_alku
                    ero = ero.days + 1
                    putkilista.append(ero)

            kesto = round(statistics.mean(putkilista), 2)

            return keskihinta, kesto
        return "", ""

    if haettava == 'kaikki' and tulosta:
        print('<p>Keskimääräinen hinta/päivä ja kesto</p>')
        print('<tr><th>Tuote</th><th>Hinta</th><th>Kesto</th></tr>')

        for tuote in df.columns:
            keskihinta, kesto = haku(tuote)
            print(f'<tr><td>{tuote}</td>')
            if keskihinta == "":
                print('<td colspan=3>Ei tietoja</td></tr>')
            else:
                print(f'<td>{keskihinta}</td>')
                print(f'<td>{kesto}</td></tr>')
    else:
        keskihinta, kesto = haku(haettava)
        print(
            f'<tr><td>Päivittäinen keskihinta</td><td>{keskihinta}</td></tr>')
        print(f'<tr><td>Keskimääräinen kesto</td><td>{kesto}</td></tr>')



def e_kcal():
    df = kululasku()
    kulutus = ravintolasku.kaikki()
    kalorit = kulutus['energia_kJ']*0.23/1000
    a = pd.to_datetime(input('Anna kuukausi numeroina (k(k) vvvv): '))
    if a.strftime('%m') in ['01', '03', '05', '07', '08', '10', '12']:
        days = 31
    elif a.strftime('%Y-%m') == '2020-02':
        days = 29
    elif a.strftime('%m') == '02':
        days = 28
    else:
        days = 30
    b = df[a.strftime('%Y-%m-%d'):(a+dt.timedelta(days=days-1)
                                   ).strftime('%Y-%m-%d')].sum(axis=1)
    kalorit = kalorit[a.strftime(
        '%Y-%m-%d'):(a+dt.timedelta(days=days-1)).strftime('%Y-%m-%d')]
    tulos = b/kalorit
    tulos.plot.bar()
    plt.show()


def hinta_scatter():
    df = kululasku()
    paivahinnat = df.sum(axis=1)
    tuote = str.capitalize(input('Anna tuote: '))
    tuotehinnat = df[tuote]
    temp_df = paivahinnat.to_frame()
    temp_df[tuote] = tuotehinnat

    temp_df.plot.scatter(y=0, x=tuote)
    plt.show()


def kk_summat():
    '''
    Kuukausien ostosten summat pylväinä. 
    Lisänä myös laskennallinen osuus vielä loppumattomille tuotteille.
    '''
    df = kululasku()
    paivasummat = df.sum(axis=1)
    kk = paivasummat.resample('M').sum()

    # loppumattomien lisäys
    valitsin = sql_yhteys.yhteys()

    sql = '''SELECT pvm_alku, hinta FROM ostos
            WHERE pvm_loppu IS NULL'''
    valitsin.execute(sql)
    tulokset = valitsin.fetchall()
    tanaan = datetime.now().date()
    paivat = pd.date_range(tulokset[0][0], tanaan)
    data = np.zeros(len(paivat))

    for pvm in tulokset:
        ero = (tanaan - pvm[0]).days
        alku = (pvm[0] - tulokset[0][0]).days
        for i in range(ero + 1):
            data[alku+i] += float(pvm[1].replace(',', '.')) / (ero + 1)
    laskennalliset = pd.Series(data=data, index=paivat)

    lisa = laskennalliset.resample('M').sum()
    kk = kk.to_frame(name='Toteutuneet')

    kk['Laskennalliset'] = lisa

    fig, axes = plt.subplots(1,1)
    fig.set_size_inches(12, 5)
    kk.plot(stacked=True, ax=axes, kind='bar')

    # x-akselin muotoilu
    x_labels = kk.index.strftime('%m/%y')
    axes.set_xticklabels(x_labels, rotation=0)
    # vaakaviivat
    axes.grid(axis='y', dashes=(10, 10))

    #linkkien asettaminen
    url = '/ruoka/merkinnat/tarkastelu/kasittely.php?funktio=paivasumma&'
    for i, child in enumerate(axes.containers[0].get_children()):
        vuosi = kk.index[i].strftime('%Y')
        kuukausi = kk.index[i].strftime('%m')
        child.set_url(url + f'vuosi={vuosi}&kk={kuukausi}')

    plt.savefig(KUVIEN_POLKU + 'kk_summat.svg', bbox_inches='tight')
    print(kk.to_html(border=0))
    sql_yhteys.sulje_yhteys(valitsin)

def kuluosuudet():
    # kaikki kulut
    df = kululasku()
    paivasummat = df.sum(axis=1)
    kk = paivasummat.resample('M').sum()
    kk.index = kk.index.strftime('%m-%Y')

    def yksittainen_tuote():  # tuotteen osuus kuluista
        tuote = input('Valitse tuote: ').capitalize()
        kulut = df[tuote].resample('M').sum()
        kulut.index = kulut.index.strftime('%m-%Y')

        tuoteosuudet = round(kulut/kk*100, 2)

        print('Tuotteen %-osuus kuukauden kuluista')
        print(tuoteosuudet)

    def kaikki_kuukausi():
        val = input('Anna kuukausi (kk-YYYY): ')
        kaikki = df.resample('M').sum()

        osuudet = kaikki.loc[val]/kk.loc[val]*100
        osuudet = osuudet.iloc[0]  # dataframesta sarja
        osuudet = osuudet.loc[(osuudet != 0)]  # poistaa nolla-arvot
        osuudet.sort_values(inplace=True)  # suurin arvo ensimmäiseksi

        osuudet.plot.barh()
        plt.title('Aineksen osuus kuukauden kuluista (%)')
        plt.grid(axis='x', dashes=(10, 10))
        plt.show()

    while True:
        print('1: Yksittäisen tuotteen osuudet \n2: Kaikkien tuotteiden osuudet yhdeltä kuukaudelta')
        eteneminen = input('Kumpaa haluat tarkastella: ')

        if eteneminen == '1':
            yksittainen_tuote()
        elif eteneminen == '2':
            kaikki_kuukausi()
        else:
            break


def ostettu_kulutettu(tuote):
    valitsin = sql_yhteys.yhteys()

    ostoshaku = f'''SELECT o.pvm_alku, o.maara
                FROM ostos o
                JOIN tuote t
                USING(tuote_id)
                WHERE t.nimi = '{tuote}'
                AND o.maara IS NOT NULL
                '''
    valitsin.execute(ostoshaku)

    ostot = valitsin.fetchall()

    kulutushaku = f'''SELECT r.pvm, SUM(r.maara)
                        FROM ruokailu r
                        JOIN tuote t
                        USING(tuote_id)
                        WHERE t.nimi = '{tuote}'
                        GROUP BY r.pvm 
                    '''
    valitsin.execute(kulutushaku)

    kulutus = valitsin.fetchall()

    paivat = pd.date_range(min(ostot[0][0], kulutus[0][0]),
                           max(ostot[-1][0], kulutus[-1][0]))
    df = pd.DataFrame(index=paivat)

    kulutus_df = pd.DataFrame(kulutus, columns=['pvm', 'kmaara'])
    kulutus_df.set_index('pvm', inplace=True)

    ostos_df = pd.DataFrame(ostot, columns=['pvm', 'omaara'], dtype=float)
    ostos_df.set_index('pvm', inplace=True)

    kaikki = pd.concat([df, kulutus_df, ostos_df], axis=1)
#    fig = plt.plot(kaikki['omaara'] - kaikki['kmaara'])

    #x_labels = kaikki.index.strftime('%d-%m-%y')
    # fig.set_xticklabels(x_labels)
 #   plt.show()

# ostettu_kulutettu('Leipä')


if __name__ == '__main__':
    funktiot = {
    'kk_summat' : kk_summat,
    'keskim' : keskim,
    'paivasumma' : paivasumma,
    'paivien_kulut' : paivien_kulut,
    'tuotehinta_tod_nak' : tuotehinta_tod_nak,
    }

    parser = argparse.ArgumentParser()
    parser.add_argument('prog', help='Käytetty ohjelma')  # voisi laittaa omaan niin voi monesta tiedostosta kutsua funktioita
    parser.add_argument('funktio', help='Käytetty funktio', choices=funktiot)
    parser.add_argument('args', help='Parametrit', action='extend', nargs='*')
    parser.add_argument('--piirra', help='Piirretäänkö kuvaaja', nargs='?', const=True)
    cmd = vars(parser.parse_args(sys.argv))
    
    kwargs = {key: cmd[key] for key in cmd if key not in ['prog','funktio','args'] and cmd[key] is not None}

    if cmd['args'] is not None:
        args = cmd['args']
    else: args = []
    
    funktiot[cmd['funktio']](*args, **kwargs)
