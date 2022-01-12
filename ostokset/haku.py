import time
import re

from selenium import webdriver

import browser_conf
from tunnushaku import tunnushaku

def main():
    # hae kirjautumiset tiedostosta
    tunnukset = tunnushaku('skanava')

    browser = browser_conf.browser()
    browser.minimize_window()
    browser.get('https://www.s-kanava.fi/web/s/oma-s-kanava/asiakasomistaja/omat-ostot')

    tunnus=browser.find_element_by_name('username')
    salas=browser.find_element_by_name('password')
    tunnus.send_keys(tunnukset['username'])
    salas.send_keys(tunnukset['password'])
    mene=browser.find_element_by_tag_name('button')
    mene.click()
    while True:
        browser.get('https://www.s-kanava.fi/web/s/oma-s-kanava/asiakasomistaja/omat-ostot#/ostotapahtumat')    
        time.sleep(5)
        lista=browser.find_element_by_tag_name('html')
        
        if len(lista.text) == 0:
            continue
        #print(lista.text)
        
        tuotteet=re.findall('€((.|\n)*)Yhteensä', lista.text)
        tuotteet=tuotteet[0][0][1:-11]
        tuotteet=tuotteet.split('\n')
        
        
        if len(tuotteet)== 0:
            continue
        else:
            break
        
    
     
    browser.quit() 
        
    
    for i in range(len(tuotteet)):
        try:
            if ' kpl' in tuotteet[i] and '12' not in tuotteet[i]:
                tuotteet[i-1]=tuotteet[i-1]+' '+tuotteet[i]
                tuotteet.pop(i)
        except:
            IndexError
        
    ostosdict={}
    for i in range(len(tuotteet)):
        if (i%2)== 1:
            ostosdict[tuotteet[i-1]]=tuotteet[i]

    return ostosdict

if __name__ == '__main__':
    main()






    
