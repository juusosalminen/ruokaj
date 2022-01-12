import mysql.connector as con
from tunnushaku import tunnushaku

tunnukset = tunnushaku('mysql')

db = con.connect(
                host = tunnukset['host'],
                user = tunnukset['username'],
                passwd = tunnukset['password'],
                auth_plugin = tunnukset['auth_plugin'],
                database = tunnukset['dbname']
                )

def yhteys():
    '''Luo mysql-yhteyden

    Returns:
        cursor: Mysql-kursori
    '''
    if not db.is_connected():
        db.reconnect()
    return db.cursor()


def sulje_yhteys(kursori):
    '''Sulkee mysql-yhteyden ja annetun kursorin

    Args:
        kursori (cursor): Mysql-kursori, joka suljetaan
    '''    
    kursori.close()
    db.close()


