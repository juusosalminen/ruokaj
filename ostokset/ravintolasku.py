#ravintotietoja
import datetime

import numpy as np
import pandas as pd

import sql_yhteys

valitsin = sql_yhteys.yhteys()

def usea(pvm, ravinne='*'):
    '''Hakee ravintotiedot aikaväliltä

    Args:
        pvm (list): Aikavälin alku ja loppu listassa
        ravinne (str, optional): Hakee vain tietyn ravinteen, muutoin kaikki. Defaults to '*'.

    Returns:
        DataFrame: Ravinteet päivittäin
    '''    
    alku = datetime.datetime.strptime(pvm[0], '%Y-%m-%d').date()
    loppu = datetime.datetime.strptime(pvm[1], '%Y-%m-%d').date()
    paivat = pd.date_range(alku, loppu, freq='d').strftime('%Y-%m-%d')
        
    pituus = (loppu-alku).days + 1

    kulutushaku =   f'''SELECT r.maara, rav.{ravinne}, r.pvm
                        FROM ruokailu r JOIN ravinto rav
                        USING(ravinto_id)
                        WHERE r.pvm BETWEEN '{pvm[0]}' AND '{pvm[1]}' '''
    valitsin.execute(kulutushaku)

    #sql-haun sarakkeet, joista poistettu muut kuin ravinteiden nimet
    sarakkeet = valitsin.description[1:2]
    if ravinne == '*': sarakkeet = valitsin.description[2:-2]

    ravinteet = {i[0]: np.zeros(pituus) for i in sarakkeet}
    syodyt = valitsin.fetchall()

    for aines in syodyt:
        maara = aines[0]
        paiva = aines[-1] 
        i = 2
        if ravinne != '*': i = 1       
        for x in range(pituus):
            if str(paiva) == paivat[x]:
                for r in ravinteet.keys():
                    tulos = 0
                    tulos += float(aines[i]) * int(maara) / 100
                    i += 1
                    ravinteet[r][x] += tulos
    
    return pd.DataFrame(index=paivat, data=ravinteet)
    

def yksittainen(pvm):    
    ravinteet={i[0]:0 for i in valitsin.description[1:-1]}
    kulutushaku =   f'''SELECT r.maara, rav.*
                        FROM ruokailu r JOIN ravinto rav
                        USING(ravinto_id)
                        WHERE r.pvm = '{pvm[0]}' '''
    valitsin.execute(kulutushaku)

    syodyt = valitsin.fetchall()

    for aines in syodyt:
        maara = aines[0]    
        i=2
        for ravinne in ravinteet.keys(): 
            ravinteet[ravinne]+=float(aines[i])*int(maara)/100
            i+=1
                    
    return ravinteet
    
def main():
    pvm=input('Anna pvm(YYYY-mm-dd): ')
    pvm=pvm.split(' ')

    if len(pvm) == 1:
        ravinteet=yksittainen(pvm)
        sarja=pd.Series(ravinteet)
        return sarja
    else:
        ravinteet, paivat=usea(pvm)
        df=pd.DataFrame(index=paivat, data=ravinteet)
        #df.to_csv('ravinnepaivat.csv')
        #print(df)
        return df
        
    
def kaikki():
    return usea(['2021-02-05', str(datetime.date.today())])



        
    
