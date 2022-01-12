from selenium import webdriver

def browser():
    options=webdriver.ChromeOptions()
    options.add_argument('log-level=3')
    browser = webdriver.Opera(executable_path=r'C:\operadriver_win64\operadriver_win64\operadriver.exe', options=options)
    return browser
