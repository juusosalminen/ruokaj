#automaattinen ravintoaineiden lisäys
import json
import re
from datetime import datetime

from selenium.common.exceptions import NoSuchElementException

import browser_conf
import sql_yhteys
from tunnushaku import tunnushaku

valitsin = sql_yhteys.yhteys()


tuotehaku = 'SELECT nimi FROM tuote'

valitsin.execute(tuotehaku)
tuotteet = valitsin.fetchall()


for i in range(len(tuotteet)):
    tuotteet[i]=tuotteet[i][0]

ANNOSTEN_POLKU = tunnushaku('polut')['annokset']

with open(ANNOSTEN_POLKU, 'r') as f:
    tiedot = json.load(f)
    ruokadict = tiedot["annokset"]
    kertoimet = tiedot["kertoimet"]
    ulkopuoliset = tiedot["ulkopuoliset"]

def valinta():
    '''
    Valitaan tunnus, jonka mukaan tallennettuja annoksia lisätään.
    Voi myös tarkastella tallennuksien sisältöä tai lisätä annoksia.
    '''
    tunnus = 'VP' #oletustunnus
    ainekset=[]
    while True:
        print('**********')
        print('H: ohjeet, L: lisää, K: kertoimet, M: muokkaus, X: lopeta')
        valinta = input('Valitse annos: ').upper()
        valinta = valinta.split(' ')
        if 'X' in valinta:
            break
        elif 'H' in valinta:
            for i in range(len(ruokadict.keys())):
                print(f'{ruokadict[list(ruokadict.keys())[i]][0]} => {list(ruokadict.keys())[i]}')
            print('Aineksia voi lisätä ja vähentää + ja - merkeillä aineksen edessä esim. +Maito -Leipä')
            jatko=input('Anna annoksen koodi, jos tahdot ainestiedot: ').upper()
            if jatko in ruokadict.keys():
                print(ruokadict[jatko][1])
            continue
        elif 'L' in valinta:
            lisaa()
            continue 
        elif 'K' in valinta:
            kertoimien_muokkaus()
            continue
        elif 'M' in valinta:
            annosten_muokkaus()
            continue
            
        for i in range(len(valinta)):
            muokattava_aines = str.capitalize(valinta[i][1:])
            if valinta[i] in ruokadict.keys():
                ainekset += (ruokadict[valinta[i]][1])
                tunnus=valinta[i] 
            elif re.match('^\+', valinta[i]) and muokattava_aines not in ainekset:
                ainekset.append(muokattava_aines)
            elif re.match('^\+', valinta[i]) and muokattava_aines in ainekset:
                print(f'Aines {muokattava_aines} on jo listassa')
            elif re.match('^\-', valinta[i]) and muokattava_aines in ainekset:
                ainekset.remove(muokattava_aines)
            elif re.match('^\-', valinta[i]) and muokattava_aines not in ainekset:
                print(f'Ainesta {muokattava_aines} ei ole listassa')
            else:
                print(f'Tuntematon tunnus {valinta[i]}')
           
            
        if len(ainekset) > 0:
            toiminta(ainekset, tunnus)
        ainekset.clear()
        

def toiminta(ainekset, tunnus):
    '''Avataan selain ja täydennetään lomake tallennetuilla tiedoilla.

    Args:
        ainekset (list): Annoksen ainekset listassa
        tunnus (str): Annoksen tunnus
    '''    
    
    nyt = datetime.now()
    nyt = nyt.strftime('%H')
    browser = browser_conf.browser()
    url = 'http://localhost/ruoka/merkinnat/ravinto/ravintomerkinta.php' 
    browser.get(url)
    luku = browser.find_element_by_name('maara')
    luku.send_keys(len(ainekset))
    mene = browser.find_element_by_name('laheta')
    mene.click()

    kohteet = browser.find_elements_by_class_name('tuote')
    
    for i in range(len(kohteet)):
        kohde = kohteet[i]
        kohde.send_keys(ainekset[i])
    mene = browser.find_element_by_name('laheta')
    mene.click()
    browser.maximize_window()

    for i in range(len(ainekset)):
        ravintoaine = browser.find_element_by_xpath(f'//*[@id="ravintoaineita{i}"]/option[1]')
        valinta = ravintoaine.get_attribute('value')
        ravintopaikka = browser.find_element_by_name(f'ravintoaine{i}')
        tuotenimi = browser.find_element_by_name(f'tuote{i}').get_attribute('value')
        if tuotenimi in tuotteet:
            ravintopaikka.send_keys(valinta)
        else:
            browser.find_element_by_name(f'valittu{i}').click()
        if tuotenimi in kertoimet:
            try:
                kerroinpaikka = browser.find_element_by_name(f'kerroin{i}') 
                kerroinpaikka.send_keys(kertoimet[tuotenimi])
            except NoSuchElementException: # kertoimen paikkaa ei ole jos tuote on loppunut
                pass
        if tuotenimi in ulkopuoliset:
            browser.find_element_by_name(f'ravintoaine{i}').send_keys(ulkopuoliset[tuotenimi][0])
            browser.find_element_by_name(f'koko{i}').send_keys(ulkopuoliset[tuotenimi][1])
    tyyppivaihtoehdot = browser.find_elements_by_name('tyyppi')
    for tyyppi in tyyppivaihtoehdot:
        tyypin_arvo=tyyppi.get_attribute('value')
        if ruokadict[tunnus][0].startswith(str.capitalize(tyypin_arvo)):
            tyyppi.click()
            break
        elif tyypin_arvo == 'lounas' and ruokadict[tunnus][0].startswith('L&P') and int(nyt) < 15:
            tyyppi.click()
            break
        elif tyypin_arvo == 'paivallinen' and ruokadict[tunnus][0].startswith('L&P'):
            tyyppi.click()


def lisaa():
    '''
    Tallennetaan uusia annoksia
    '''
    while True:
        tunnus = input('Anna annoksen tunnus: ').upper()
        if tunnus in ruokadict.keys():
            print('Tunnus on jo käytössä.')
            continue
        tyyppi = int(input('Anna aterian tyyppi: 1=Lounas/Päivällinen 2=Aamupala 3=Välipala 4=Iltapala\nOletus=1\n'))
        if tyyppi == 1:
            tyyppi = 'L&P'
        elif tyyppi == 2:
            tyyppi = 'Aamupala'
        elif tyyppi == 3:
            tyyppi = 'Valipala'
        elif tyyppi == 4:
            tyyppi = 'Iltapala'
        else: tyyppi = 'L&P'
        nimi = input('Anna annoksen nimi: ').capitalize()
        ainekset = input('Anna ainekset välilyönneillä erotettuna: ').strip()
        aineslista = [a.capitalize() for a in ainekset.split(' ')]
        print(f'Ollaan lisäämässä seuraavaa:\ntunnus : {tunnus}\ntyyppi ja nimi : {tyyppi}, {nimi}\nAinekset:', end =' ')
        for a in aineslista: print(a+' ', end=' ')
        val = input('Lisätäänkö? (k): ')
        if val.upper() == 'K':
            ruokadict[tunnus] = [tyyppi + ' ' + nimi, aineslista]
            with open(ANNOSTEN_POLKU, 'w') as f:
                json.dump(tiedot, f, indent=4)
            break

    #muotoilu, paluu, tarkistus onko tuote oikein, siistiminen (koko)

def kertoimien_muokkaus():
    '''
    Tarkastellaan ja muokataan tallennettuja kertoimia
    '''
    while True:
        print('H näyttää olemassa olevat, X lopettaa')
        valinta = input('Anna tuotteen nimi, jolle tahdot asettaa kertoimen: ').capitalize()
        if valinta == 'X':
            break
        if valinta == 'H':
            for k in kertoimet.keys():
                print(f'{k} : {kertoimet[k]}')
            continue
        if valinta in tuotteet:
            if valinta in kertoimet.keys():
                print(f'Tuotteella on jo kerroin {kertoimet[valinta]}, syötä x jos et tahdo muokata')
            kerroin = input('Tuote löytyi, anna kerroin muodossa x/x: ') 
            if kerroin.upper() == 'X': continue      
            if re.match('\d+/\d+', kerroin):
                kertoimet[valinta] = kerroin
                with open(ANNOSTEN_POLKU, 'w') as f:
                    json.dump(tiedot, f, indent=4)
                print(f'Tuotteelle {valinta} lisätty kerroin {kerroin}')
            else:
                print('Kerroin annettu väärässä muodossa')
                continue
            break     
        print('Tuotetta ei löytynyt')

def annosten_muokkaus():
    '''
    Muokataan olemassa olevia annoksia.
    '''
    while True:
        print('X: lopeta')
        tunnus = input('Anna annoksen tunnus: ').upper()
        if tunnus == 'X': break
        if tunnus not in ruokadict.keys():
            print('Tuntematon tunnus')
            continue
        valinta = input('Haluatko lisätä (L) vai poistaa (P)? ')
        ainekset = ruokadict[tunnus][1]
        if valinta.upper() == 'P':
            print('Annoksessa on seuraavat ainekset:')
            for index, aines in enumerate(ainekset):
                print(f'{index + 1} : {aines}')
            poistettava = int(input('Anna numero, jonka haluat poistaa: '))
            if poistettava > 0 and poistettava <= len(ainekset):
                ainekset.pop(poistettava - 1)
                with open(ANNOSTEN_POLKU, 'w') as f:
                    json.dump(tiedot, f, indent=4)
                print(f'{ainekset[poistettava - 2]} on poistettu.')
            else:
                print('Väärä valinta')
                continue
        elif valinta.upper() == 'L':
            lisattava = input('Minkä aineksen haluat lisätä? ').capitalize()
            ainekset.append(lisattava)
            with open(ANNOSTEN_POLKU, 'w') as f:
                json.dump(tiedot, f, indent=4)
            print(f'{lisattava} on lisätty.')
        else:
            print('Väärä valinta')



if __name__ == '__main__':
    print('Annosten lisääminen')
    valinta()

sql_yhteys.sulje_yhteys(valitsin)









