# laskettavien tuotteiden kulujen kohdistus, tarkempi kuin pvm mukainen
from datetime import datetime

import pandas as pd

import sql_yhteys

def main():
    valitsin = sql_yhteys.yhteys()
    haku='''       
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
        AND r.tuote_id = o2.tuote_id)
    '''

    valitsin.execute(haku)

    tulokset=valitsin.fetchall()
    kulut = {}
    j=1
    lisakulut={}
    kulutettu = 0
    for rivi in tulokset:
        pvm = rivi[2].strftime('%Y-%m-%d')
        tuote = rivi[0]
        kulu = round(float(rivi[3].replace(',','.'))*int(rivi[1])/int(rivi[4]), 5)
        if tuote in kulut.keys():
            kulut[tuote].append((pvm, kulu))
        else:
            kulut[tuote]=[(pvm,kulu)]
        #jos merkittyä kulutusta vähemmän kuin ostettu
        
        if rivi[6] == tulokset[j-1][6] and rivi[5] == tulokset[j-1][5]:
            ostospaino = int(tulokset[j-1][4])
            kulutettu += int(tulokset[j-1][1])
            
        elif tulokset[j-1][6] == tulokset[j-2][6] and tulokset[j-1][5] == tulokset[j-2][5]:
            ostospaino = int(tulokset[j-1][4])
            kulutettu += int(tulokset[j-1][1])
            
            if ostospaino-kulutettu > 5: 
                #erotus jaetaan päivien kesken
                paivaero=tulokset[j-1][6]-tulokset[j-1][5]
                lisattava=(ostospaino-kulutettu)/paivaero.days
                hintalisa=lisattava/int(tulokset[j-1][4])*float(tulokset[j-1][3].replace(',','.'))
                paivat = pd.date_range(tulokset[j-1][5],tulokset[j-1][6])
                for i in range(paivaero.days):
                    if tulokset[j-1][0] in lisakulut.keys():
                        lisakulut[tulokset[j-1][0]].append((paivat[i].strftime('%Y-%m-%d'), hintalisa))
                    else:
                        lisakulut[tulokset[j-1][0]]=[(paivat[i].strftime('%Y-%m-%d'), hintalisa)]                                                    
            kulutettu=0
        j+=1
    

    
    alku = '2021-02-07'

    index=pd.date_range(alku, datetime.now())
    df=pd.DataFrame(index=index, columns=kulut.keys())
    lk_df=pd.DataFrame(index=index, columns=kulut.keys())

    for i in range(len(lisakulut.keys())):
        avain=list(lisakulut.keys())[i]
        for j in range(len(lisakulut[avain])):
            lk_df[avain][lisakulut[avain][j][0]]=lisakulut[avain][j][1]


    for i in range(len(kulut.keys())):
        avain=list(kulut.keys())[i]
        for j in range(len(kulut[avain])):
            df[avain][kulut[avain][j][0]]=kulut[avain][j][1]
    df=df.add(lk_df, fill_value=0)
    df.fillna(0, inplace=True)
    
    sql_yhteys.sulje_yhteys(valitsin)
    return df


if __name__ == '__main__':
    print(main())






    
