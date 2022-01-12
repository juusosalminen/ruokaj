#Tuotteiden haku kassakuitista, helpottaa painon saamista

import time
import re

import arrow 
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

import browser_conf
from tunnushaku import tunnushaku

def main():
    #hae tunnukset
    tunnukset = tunnushaku('skanava')

    browser = browser_conf.browser()
    browser.set_window_size(1024,768)
    browser.minimize_window()
    browser.get('https://www.s-kanava.fi/web/s/oma-s-kanava/asiakasomistaja/kassakuitit')

    tunnus=browser.find_element_by_name('username')
    salas=browser.find_element_by_name('password')
    tunnus.send_keys(tunnukset['username'])
    salas.send_keys(tunnukset['password'])
    mene=browser.find_element_by_tag_name('button')
    mene.click()

    try:
        kuitti = WebDriverWait(browser, 15).until(\
            EC.presence_of_element_located((By.TAG_NAME, 'td')))

    finally:
        kuitti.click()

    tiedot= browser.find_element_by_xpath('//*[@id="printableArea"]/pre')
    tiedot=tiedot.text

    tanaan=arrow.now().format('D-MM-YYYY')
    index_alku=tiedot.find(tanaan)
    index_loppu=tiedot.find('YHTEENSÄ')

    tiedot=tiedot[index_alku+(len(tanaan))+1:index_loppu-len('YHTEENSÄ')-6]
    pattern = re.compile(r'\s+')
    tiedot = re.sub(pattern, ' ', tiedot)
    
    paino=re.compile('[KPL\dG]*([A-Z\s]*)\s*\d\.\d{2}\s*(\d\.\d{3}\sKG)')
    tuote=re.findall(paino, tiedot)
    
    

    tuotepaino={}
    for i in range(len(tuote)):
        nimi=re.findall('[A-Z]+', tuote[i][0])
        paino=re.findall('\d\.\d{3}', tuote[i][1])
        tuotepaino[nimi[0]]=int(float(paino[0])*1000)

    browser.close()
    
    return tuotepaino
     
if __name__ == '__main__':
    main()
    


