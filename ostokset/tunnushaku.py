from configparser import ConfigParser

def tunnushaku(section, filename=r'C:\xampp\htdocs\ruoka\ostokset\conf.ini'):
    parser = ConfigParser()
    parser.read(filename)

    tiedot = {}
    if parser.has_section(section):
        arvot = parser.items(section)
        for arvo in arvot:
            tiedot[arvo[0]] = arvo [1]
    else:
        raise Exception(f'Kohdetta {section} ei löydy') 

    return tiedot
