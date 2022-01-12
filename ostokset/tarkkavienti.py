import re
import time

from selenium import webdriver
from selenium.webdriver.support.ui import Select

import browser_conf
import haku
import kuitista

def main():
    ostokset=sala.main()
    valinta=input('Paina K, jos ostit kilohintaisia: ')
    if valinta.upper() == 'K':
        painot=kuitista.main()
    else:
        painot={}

    maara=len(ostokset.keys())

    browser=browser_conf.browser()
    url='http://localhost/ostokset/alku.html' 
    browser.get(url)

    luku=browser.find_element_by_name('maara')
    luku.send_keys(maara)
    mene=browser.find_element_by_name('laheta-auto')
    mene.click()


    nimet=list(ostokset.keys())
    hinnat=list(ostokset.values())
    valinnat=browser.find_elements_by_tag_name('option')

    tunnistusdict={
        'ruispala':'Leipä',
        'valkosipuli':'Valkosipuli',
        'suikale':'Kanasuikale',
        'rahka':'Maitorahka',
        'alaskanseiti':'Kala',
        'koipipalat':'Kanankoipi'

    }
    painotunnistus={
        'ruthin ruispala' : 500,
        'rahka' : 500,
        'rusinat': 250,
        'margariini' : 600,
        'täysjyväpikariisi' : 1000,
        'appelsiinimehu' : 1000
    }

    for tuotenimi in nimet:
        kerroin=re.findall('\d\skpl$', tuotenimi)
        for tunniste in list(painotunnistus.keys()):
            if kerroin and tunniste in tuotenimi.lower():
                painot[tuotenimi] = painotunnistus[tunniste]*int(kerroin[0][0])
            elif tunniste in tuotenimi.lower():
                painot[tuotenimi]=painotunnistus[tunniste]
        else:
            paino=re.findall('\d+\s*g', tuotenimi)
            if paino and kerroin:
                painot[tuotenimi] = int(paino[0][:-1])*int(kerroin[0][0])
            elif paino and not kerroin:
                painot[tuotenimi] = paino[0][:-1]
            else:
                paino=re.findall('\d\s*[kK][gG]', tuotenimi)
                if paino and kerroin:
                    painot[tuotenimi] = int(paino[0][0])*int(kerroin[0][0])*1000
                elif paino and not kerroin:
                    painot[tuotenimi] = int(paino[0][0])*1000
                else:
                    paino=re.findall('\s\d\s*[lL][^k\d]*',tuotenimi)
                    if paino and kerroin:
                        painot[tuotenimi] = int(paino[0][1])*int(kerroin[0][0])*1000
                    elif paino and not kerroin:
                        painot[tuotenimi] = int(paino[0][1])*1000
                    else:
                        paino=re.findall('\d.\d+\s*[lL]', tuotenimi)
                        if paino and kerroin:
                            painot[tuotenimi] = int(float(paino[0][:4].replace(',','.')))*int(kerroin[0][0])*1000
                        elif paino and not kerroin:
                            painot[tuotenimi] = int(float(paino[0][:4].replace(',','.'))*1000)
                        else:
                            paino = re.findall('\d\s*dl', tuotenimi)
                            if paino and kerroin:
                                painot[tuotenimi] = int(paino[0][0])*int(kerroin[0][0])*100
                            elif paino and not kerroin:
                                painot[tuotenimi] = int(paino[0][0])*100


    for i in range(maara):
        hintapaikka=browser.find_element_by_name(f'hinta{i}')
        nimipaikka=browser.find_element_by_name(f'tarkka{i}')
        tuotepaikka=browser.find_element_by_name(f'tuote{i}')
        painopaikka=browser.find_element_by_name(f'paino{i}')
        hintapaikka.send_keys(hinnat[i])
        nimipaikka.send_keys(nimet[i])
        
        for x in range(len(painot.keys())):
            if list(painot.keys())[x].upper() in nimet[i].upper():
                painopaikka.send_keys(list(painot.values())[x])

        
        for j in range(len(list(tunnistusdict.keys()))):
            if list(tunnistusdict.keys())[j] in str.lower(nimet[i]):
                tuotepaikka.send_keys(list(tunnistusdict.values())[j])
                break
        else:
            for kohde in valinnat: 
                if str.lower(kohde.get_attribute('value')) in str.lower(nimet[i]):
                    tuotepaikka.send_keys(kohde.get_attribute('value'))
                    break
                


if __name__ == '__main__':
    main()
                
