import telebot
from telebot.types import InlineKeyboardMarkup, InlineKeyboardButton, ReplyKeyboardMarkup, KeyboardButton
import time
import threading
import requests
import json
import os
import base64
from io import BytesIO
from datetime import datetime
import functools
from cachetools import TTLCache
import concurrent.futures


ELITEPAY_CONFIG = {
    "base_url": "https://api.elitepaybr.com/api",
    "client_id": "seu_client_id_aqui",  # ALTERE PARA SEU CLIENT ID
    "client_secret": "seu_client_secret_aqui",  # ALTERE PARA SEU CLIENT SECRET
    "webhook_secret": "seu_webhook_secret_aqui"  # ALTERE SE FOR NECESSÁRIO
}

# Token do bot Telegram
TOKEN = ""
bot = telebot.TeleBot(TOKEN, threaded=True, num_threads=5)


GRUPO_PRIVADO_LINK = "https://t.me/privacyfree25"

# Caches para otimização
video_cache = TTLCache(maxsize=10, ttl=3600)
user_cache = TTLCache(maxsize=1000, ttl=300)
transaction_cache = TTLCache(maxsize=100, ttl=600)

# Dicionários para armazenar dados
user_data = {}
user_activity = {}
user_transactions = {}
pending_payments = {}
confirmed_payments = set() 


if not os.path.exists("videos"):
    os.makedirs("videos")


VIDEOS = {
    "start": "videos/video_inicial.mp4",
    "mensal": "videos/video_mensal.mp4",
    "vitalicio": "videos/video_vitalicio.mp4",
    "vip": "videos/video_vip.mp4"
}

# Texto da legenda inicial
LEGENDA_INICIAL = """⚠️ 𝘾𝙖𝙣𝙨𝙖𝙙𝙤 𝙙𝙚 𝙫𝙚𝙧 𝙘𝙤𝙣𝙩𝙚𝙪́𝙙𝙤𝙨 𝙧𝙚𝙥𝙚𝙩𝙞𝙙𝙤𝙨 𝙚 𝙫𝙚𝙡𝙝𝙤𝙨? 👀

🇺🇸😈 𝐏𝐀𝐑𝐀Í𝐒𝐎 𝐃AS NOV1NHAS 🔥

💦𝐍𝐨𝐯𝐢𝐧𝐡𝐚𝐬⁺¹⁸ 𝐝𝐚 𝐍𝟑𝐓⁺¹⁸ 
👹𝐈𝐧𝐜𝟑𝐬𝐭𝐨 𝐒𝐞𝐜𝐫𝟑𝐭𝐨 𝐑𝐄𝐀𝐋⁺¹⁸
🔥𝙏𝙄𝙊 𝙀 𝙎𝙊𝘽𝙍𝙄𝙉𝙃𝘼 ⁺¹⁸
😱 𝐏𝐚𝐝𝐫𝐚𝐬𝐭𝐫𝐨 𝐜𝐨𝐦𝐞𝐧𝐝𝐨 𝐍𝐨𝐯𝐢𝐧𝐡𝐚⁺¹⁸
🥛 𝐋𝟑𝐢𝐭𝐢𝐧𝐡𝟎 𝐧𝐚 𝐛𝐨𝐜𝐚 𝐝𝐚 𝐓𝐢𝐭𝐢𝐚⁺¹⁸ 
🤫 𝗢𝗰𝘂𝗹𝘁𝗼𝘀 𝗕𝗿𝘂𝘁𝗮𝗹
🥴 𝐍𝐨𝐯𝐢𝐧𝐡𝐚⁺¹⁸ 
💎 𝗩𝗶𝗱𝗲𝗼𝘀 𝘃𝗮𝘇𝗮𝗱𝗼𝘀 𝗿𝗮𝗿𝗼𝘀
🔥 𝙎𝙀𝙓𝙊 𝙉𝘼 𝙐𝙉𝙄𝙑𝙀𝙍𝙎𝙄𝘿𝘼𝘿𝙀 

🟩 𝐀𝐜𝐞𝐬𝐬𝐞 𝐨 𝐩𝐥𝐚𝐧𝐨 𝐕𝐈𝐓𝐀𝐋Í𝐂𝐈𝐎 𝐡𝐨𝐣𝐞 𝐞
𝐆𝐀𝐍𝐇𝐄 + 18 𝐠𝐫𝐮𝐩𝐨𝐬 🟩

𝗣𝗢𝗥 𝗧𝗘𝗠𝗣𝗢 𝗟𝗜𝗠𝗜𝗧𝗔𝗗𝗢 
⬇️ 𝗰𝗼𝗿𝗿𝗲 𝗲 𝗚𝗮𝗿𝗮𝗻𝘁𝗮 𝘀𝘂𝗮 𝘃𝗮𝗴𝗮!!"""



def threaded(fn):
    """Decorator para executar funções em threads separadas"""
    @functools.wraps(fn)
    def wrapper(*args, **kwargs):
        thread = threading.Thread(target=fn, args=args, kwargs=kwargs, daemon=True)
        thread.start()
        return thread
    return wrapper

def cache_result(ttl=300):
    """Decorator para cache de resultados"""
    cache = TTLCache(maxsize=100, ttl=ttl)
    
    def decorator(func):
        @functools.wraps(func)
        def wrapper(*args, **kwargs):
            key = str(args) + str(kwargs)
            if key in cache:
                return cache[key]
            result = func(*args, **kwargs)
            cache[key] = result
            return result
        return wrapper
    return decorator



def get_headers():
    """Retorna headers padrão para API"""
    return {
        "x-client-id": ELITEPAY_CONFIG["client_id"],
        "x-client-secret": ELITEPAY_CONFIG["client_secret"],
        "Content-Type": "application/json"
    }

@cache_result(ttl=60)
def check_balance():
    """Verifica saldo da conta"""
    try:
        url = f"{ELITEPAY_CONFIG['base_url']}/users/balance"
        response = requests.get(url, headers=get_headers(), timeout=5)
        
        if response.status_code == 200:
            data = response.json()
            return {"success": True, "balance": data.get("data", {}).get("balance", 0)}
        else:
            return {"success": False, "error": f"Erro {response.status_code}"}
    except Exception as e:
        return {"success": False, "error": str(e)}

def create_deposit(user_id, amount, plano, user_name="Cliente", user_document="00000000000"):
    """Cria um depósito PIX"""
    try:
        url = f"{ELITEPAY_CONFIG['base_url']}/v1/deposit"
        
        payload = {
            "amount": amount,
            "description": f"Plano {plano} - User {user_id}",
            "payerName": user_name,
            "payerDocument": user_document
        }
        
        response = requests.post(url, json=payload, headers=get_headers(), timeout=10)
        
        if response.status_code == 200:
            data = response.json()
            
            transaction_id = data.get("transactionId", f"tx_{int(time.time())}_{user_id}")
            
            pending_payments[transaction_id] = {
                "user_id": user_id,
                "plano": plano,
                "valor": amount,
                "time": time.time(),
                "status": "PENDENTE"
            }
            
            if user_id not in user_transactions:
                user_transactions[user_id] = []
            
            user_transactions[user_id].append({
                "id": transaction_id,
                "plano": plano,
                "valor": amount,
                "status": "PENDENTE",
                "time": time.time()
            })
            
            qrcode_data = data.get("qrcodeUrl", "")
            copy_paste = data.get("copyPaste", "")
            
            return {
                "success": True,
                "transaction_id": transaction_id,
                "qrcode": qrcode_data,
                "copy_paste": copy_paste,
                "status": data.get("status", "PENDENTE")
            }
        else:
            return {"success": False, "error": f"API Error: {response.status_code}", "details": response.text}
    except Exception as e:
        return {"success": False, "error": str(e)}

@cache_result(ttl=30)
def check_transaction_status(transaction_id):
    """Verifica status de uma transação"""
    try:
        url = f"{ELITEPAY_CONFIG['base_url']}/v1/transaction/{transaction_id}"
        response = requests.get(url, headers=get_headers(), timeout=5)
        
        if response.status_code == 200:
            data = response.json()
            transaction = data.get("transaction", {})
            
            return {
                "success": True,
                "status": transaction.get("transactionState", "PENDENTE"),
                "value": transaction.get("value", 0)
            }
        else:
            return {"success": False, "error": f"Erro {response.status_code}"}
    except Exception as e:
        return {"success": False, "error": str(e)}

def send_group_link(user_id):
    """Envia o link do grupo privado para o usuário"""
    try:
        markup = InlineKeyboardMarkup()
        markup.row(InlineKeyboardButton("🔞 ENTRAR NO GRUPO PRIVADO", url=GRUPO_PRIVADO_LINK))
        
        bot.send_message(
            user_id,
            f"✅ *PAGAMENTO CONFIRMADO!*\n\n"
            f"🔥 *PARABÉNS! Seu acesso foi liberado!*\n\n"
            f"Clique no botão abaixo para entrar no grupo privado:\n\n"
            f"🔗 {GRUPO_PRIVADO_LINK}",
            parse_mode="Markdown",
            reply_markup=markup
        )
        
        
        bot.send_message(
            user_id,
            f"📌 *LINK DE ACESSO:*\n{GRUPO_PRIVADO_LINK}",
            parse_mode="Markdown"
        )
        
        return True
    except Exception as e:
        print(f"Erro ao enviar link do grupo: {e}")
        return False

@threaded
def process_webhook(data):
    """Processa webhook de confirmação de pagamento"""
    try:
        transaction_id = data.get("transaction", {}).get("transactionId")
        status = data.get("transaction", {}).get("transactionState")
        
        if transaction_id and status == "COMPLETO":
            
            if transaction_id in confirmed_payments:
                return False
            
            if transaction_id in pending_payments:
                payment_info = pending_payments[transaction_id]
                user_id = payment_info["user_id"]
                plano = payment_info["plano"]
                
                
                pending_payments[transaction_id]["status"] = "COMPLETO"
                confirmed_payments.add(transaction_id)
                
                
                if user_id in user_transactions:
                    for tx in user_transactions[user_id]:
                        if tx["id"] == transaction_id:
                            tx["status"] = "COMPLETO"
                            break
                
                
                send_group_link(user_id)
                
                
                try:
                    admin_id = 123456789  # ALTERE PARA SEU ID
                    bot.send_message(
                        admin_id,
                        f"💰 *PAGAMENTO CONFIRMADO!*\n\n"
                        f"👤 Usuário: {user_id}\n"
                        f"📦 Plano: {plano}\n"
                        f"💵 Valor: R$ {payment_info['valor']:.2f}\n"
                        f"🔗 Link enviado: {GRUPO_PRIVADO_LINK}",
                        parse_mode="Markdown"
                    )
                except:
                    pass
                
                return True
    except Exception as e:
        print(f"Erro no webhook: {e}")
    
    return False



@threaded
def check_inactivity():
    """Monitora usuários inativos por 3 minutos"""
    while True:
        time.sleep(30)
        current_time = time.time()
        
        for user_id, last_active in list(user_activity.items()):
            if current_time - last_active > 180:
                try:
                    img_url = "https://www.pornolandia.xxx/media/photos/249948.jpg"
                    
                    caption = """⚠️ *PERCEBI QUE VOCÊ SUMIU!* 

Estamos te esperando no grupo privado, querido! 🔥

*Aproveite AGORA:*
✅ Promoção por tempo limitado
✅ Acesso imediato
✅ Conteúdo exclusivo

⬇️ *ESCOLHA SEU PLANO ABAIXO:*"""

                    markup = InlineKeyboardMarkup(row_width=2)
                    markup.row(
                        InlineKeyboardButton("🔥 MENSAL R$18", callback_data="plano_mensal"),
                        InlineKeyboardButton("💎 VITALÍCIO R$19,90", callback_data="plano_vitalicio")
                    )
                    markup.row(
                        InlineKeyboardButton("👑 VIP R$13,90", callback_data="plano_vip")
                    )
                    
                    try:
                        img_response = requests.get(img_url, timeout=5)
                        if img_response.status_code == 200:
                            img_data = BytesIO(img_response.content)
                            bot.send_photo(user_id, img_data, caption=caption, parse_mode="Markdown", reply_markup=markup)
                        else:
                            bot.send_message(user_id, caption, parse_mode="Markdown", reply_markup=markup)
                    except:
                        bot.send_message(user_id, caption, parse_mode="Markdown", reply_markup=markup)
                    
                    del user_activity[user_id]
                except:
                    pass

@threaded
def check_payment_timeout():
    """Verifica pagamentos pendentes e confirma automático"""
    while True:
        time.sleep(30)  
        current_time = time.time()
        
        for tx_id, payment in list(pending_payments.items()):
            if payment["status"] == "PENDENTE":
                
                status_check = check_transaction_status(tx_id)
                
                if status_check["success"] and status_check["status"] == "COMPLETO":
                    
                    if tx_id not in confirmed_payments:
                        payment["status"] = "COMPLETO"
                        confirmed_payments.add(tx_id)
                        user_id = payment["user_id"]
                        
                        
                        send_group_link(user_id)
                        
                        
                        if user_id in user_transactions:
                            for tx in user_transactions[user_id]:
                                if tx["id"] == tx_id:
                                    tx["status"] = "COMPLETO"
                                    break
                
                elif current_time - payment["time"] > 1800:  
                    
                    user_id = payment["user_id"]
                    try:
                        bot.send_message(
                            user_id,
                            f"⏰ *PAGAMENTO EXPIRADO*\n\n"
                            f"Seu PIX do plano {payment['plano']} expirou.\n"
                            f"Gere um novo pagamento se ainda tiver interesse.",
                            parse_mode="Markdown"
                        )
                    except:
                        pass


check_inactivity()
check_payment_timeout()



def send_fast_message(user_id, text, markup=None, parse_mode="Markdown"):
    """Envia mensagem de forma otimizada"""
    try:
        bot.send_message(user_id, text, parse_mode=parse_mode, reply_markup=markup)
    except:
        pass

def send_fast_video(user_id, video_path, caption, markup=None):
    """Envia vídeo de forma otimizada"""
    try:
        if os.path.exists(video_path):
            with open(video_path, 'rb') as video:
                bot.send_video(
                    user_id, 
                    video, 
                    caption=caption, 
                    parse_mode="HTML", 
                    reply_markup=markup,
                    timeout=30,
                    supports_streaming=True
                )
            return True
        else:
            send_fast_message(user_id, caption, markup)
            return False
    except Exception as e:
        print(f"Erro ao enviar vídeo: {e}")
        send_fast_message(user_id, caption, markup)
        return False

# ===== HANDLERS DO BOT =====

@bot.message_handler(commands=['start'])
def start_command(message):
    user_id = message.from_user.id
    
    markup = ReplyKeyboardMarkup(resize_keyboard=True, one_time_keyboard=True)
    markup.row(
        KeyboardButton("🔞 +18"),
        KeyboardButton("🚫 -18")
    )
    
    bot.send_message(
        user_id,
        "🔞 *VERIFICAÇÃO DE IDADE*\n\nVocê tem mais de 18 anos?",
        parse_mode="Markdown",
        reply_markup=markup
    )
    
    user_data[user_id] = {'awaiting_age': True}

@bot.message_handler(func=lambda message: user_data.get(message.from_user.id, {}).get('awaiting_age', False))
def handle_age_response(message):
    user_id = message.from_user.id
    
    if "+18" in message.text or "Tenho +18" in message.text:
        user_data[user_id] = {'age_verified': True, 'authorized': True}
        user_activity[user_id] = time.time()
        
        markup = ReplyKeyboardMarkup(resize_keyboard=True)
        markup.row(KeyboardButton("🔥 PLANOS"), KeyboardButton("💰 PAGAMENTOS"))
        
        bot.send_message(
            user_id,
            "✅ *ACESSO LIBERADO!*",
            parse_mode="Markdown",
            reply_markup=markup
        )
        
        markup_videos = InlineKeyboardMarkup(row_width=2)
        markup_videos.row(
            InlineKeyboardButton("📱 MENSAL R$18", callback_data="plano_mensal"),
            InlineKeyboardButton("💎 VITALÍCIO R$19,90", callback_data="plano_vitalicio")
        )
        markup_videos.row(
            InlineKeyboardButton("👑 VIP R$13,90", callback_data="plano_vip")
        )
        
        send_fast_video(user_id, VIDEOS["start"], LEGENDA_INICIAL, markup_videos)
    else:
        bot.send_message(
            user_id,
            "🚫 *ACESSO NEGADO*\n\nConteúdo apenas para +18.",
            parse_mode="Markdown"
        )
        user_data[user_id] = {'authorized': False}
    
    user_data[user_id]['awaiting_age'] = False

@bot.callback_query_handler(func=lambda call: True)
def handle_callbacks(call):
    user_id = call.message.chat.id
    
    if not user_data.get(user_id, {}).get('authorized', False):
        bot.answer_callback_query(call.id, "Confirme +18 primeiro!")
        return
    
    user_activity[user_id] = time.time()
    
    if call.data == "plano_mensal":
        show_plan_video(user_id, "mensal", "🔥 *MENSAL R$18*")
    elif call.data == "plano_vitalicio":
        show_plan_video(user_id, "vitalicio", "💎 *VITALÍCIO R$19,90*")
    elif call.data == "plano_vip":
        show_plan_video(user_id, "vip", "👑 *VIP R$13,90*")
    elif call.data.startswith("pagar_"):
        plano = call.data.replace("pagar_", "")
        process_payment(user_id, plano)
    elif call.data.startswith("status_"):
        tx_id = call.data.replace("status_", "")
        check_payment_status(user_id, tx_id)
    elif call.data == "voltar":
        show_main_menu(user_id)

def show_plan_video(user_id, plano, titulo):
    """Mostra vídeo do plano com botão de pagamento"""
    markup = InlineKeyboardMarkup()
    markup.row(InlineKeyboardButton("💰 PAGAR", callback_data=f"pagar_{plano}"))
    markup.row(InlineKeyboardButton("🔙 VOLTAR", callback_data="voltar"))
    
    send_fast_video(user_id, VIDEOS[plano], titulo, markup)

def process_payment(user_id, plano):
    """Processa pagamento via API ElitePay"""
    
    valores = {
        "mensal": 18.00,
        "vitalicio": 19.90,
        "vip": 13.90
    }
    
    valor = valores.get(plano, 18.00)
    
    bot.send_message(user_id, "⏳ *Gerando PIX...*", parse_mode="Markdown")
    
    @threaded
    def process():
        try:
            user = bot.get_chat(user_id)
            user_name = user.first_name or "Cliente"
        except:
            user_name = "Cliente"
        
        deposit = create_deposit(user_id, valor, plano, user_name)
        
        if deposit["success"]:
            qrcode_data = deposit.get("qrcode", "")
            copy_paste = deposit.get("copy_paste", "")
            tx_id = deposit["transaction_id"]
            
            markup = InlineKeyboardMarkup()
            markup.row(InlineKeyboardButton("✅ JÁ PAGUEI", callback_data=f"status_{tx_id}"))
            markup.row(InlineKeyboardButton("🔙 VOLTAR", callback_data="voltar"))
            
            pix_text = f"""💳 *PIX {plano.upper()} - R${valor:.2f}*

📋 *COPIA E COLA:*
`{copy_paste}`

⏰ *Expira em 30 minutos*

✅ Após o pagamento, clique em JÁ PAGUEI ou aguarde a confirmação automática."""

            if qrcode_data and qrcode_data.startswith("base64:"):
                try:
                    base64_str = qrcode_data.replace("base64:", "")
                    qr_bytes = base64.b64decode(base64_str)
                    qr_image = BytesIO(qr_bytes)
                    qr_image.name = 'qrcode.png'
                    
                    bot.send_photo(user_id, qr_image, caption=pix_text, parse_mode="Markdown", reply_markup=markup)
                except:
                    bot.send_message(user_id, pix_text, parse_mode="Markdown", reply_markup=markup)
            else:
                bot.send_message(user_id, pix_text, parse_mode="Markdown", reply_markup=markup)
        else:
            fallback_pix(user_id, plano, valor)
    
    process()

def fallback_pix(user_id, plano, valor):
    """Fallback para PIX manual"""
    pix_key = "paulohenriquedev@yahoo.com"
    
    markup = InlineKeyboardMarkup()
    markup.row(InlineKeyboardButton("✅ ENVIAR COMPROVANTE", callback_data=f"comprovante_{plano}"))
    markup.row(InlineKeyboardButton("🔙 VOLTAR", callback_data="voltar"))
    
    pix_text = f"""⚠️ *PAGAMENTO MANUAL*

💳 *{plano.upper()} - R${valor:.2f}*
📌 *CHAVE PIX:* `{pix_key}`

📋 *INSTRUÇÕES:*
1️⃣ Faça o PIX para a chave acima
2️⃣ Envie o comprovante aqui
3️⃣ Aguarde a confirmação (até 5 minutos)"""

    bot.send_message(user_id, pix_text, parse_mode="Markdown", reply_markup=markup)

def check_payment_status(user_id, transaction_id):
    """Verifica status do pagamento"""
    
    status_check = check_transaction_status(transaction_id)
    
    if status_check["success"]:
        status = status_check["status"]
        
        if status == "COMPLETO":
            if transaction_id not in confirmed_payments:
                confirmed_payments.add(transaction_id)
                
                send_group_link(user_id)
                
                
                if transaction_id in pending_payments:
                    pending_payments[transaction_id]["status"] = "COMPLETO"
            else:
               
                send_group_link(user_id)
        
        elif status == "PENDENTE":
            if transaction_id in pending_payments:
                plano = pending_payments[transaction_id]["plano"]
                time_left = 1800 - (time.time() - pending_payments[transaction_id]["time"])
                minutes_left = max(0, int(time_left / 60))
                
                markup = InlineKeyboardMarkup()
                markup.row(InlineKeyboardButton("🔄 VERIFICAR NOVAMENTE", callback_data=f"status_{transaction_id}"))
                
                bot.send_message(
                    user_id,
                    f"⏳ *PAGAMENTO PENDENTE*\n\n"
                    f"Plano: {plano}\n"
                    f"Tempo restante: {minutes_left} minutos\n\n"
                    f"Assim que o pagamento for confirmado, você receberá o link do grupo automaticamente!",
                    parse_mode="Markdown",
                    reply_markup=markup
                )
            else:
                bot.send_message(user_id, "Transação não encontrada.")
        else:
            bot.send_message(user_id, f"Status da transação: {status}")
    else:
        bot.send_message(user_id, "Erro ao verificar pagamento. Tente novamente.")

def show_main_menu(user_id):
    """Mostra menu principal"""
    markup = InlineKeyboardMarkup(row_width=2)
    markup.row(
        InlineKeyboardButton("📱 MENSAL R$18", callback_data="plano_mensal"),
        InlineKeyboardButton("💎 VITALÍCIO R$19,90", callback_data="plano_vitalicio")
    )
    markup.row(
        InlineKeyboardButton("👑 VIP R$13,90", callback_data="plano_vip")
    )
    
    send_fast_video(user_id, VIDEOS["start"], LEGENDA_INICIAL, markup)

@bot.message_handler(func=lambda message: True)
def handle_messages(message):
    user_id = message.from_user.id
    user_activity[user_id] = time.time()
    
    text = message.text
    
    if text in ["🔥 PLANOS", "/planos"]:
        show_main_menu(user_id)
    elif text in ["💰 PAGAMENTOS", "/pagamentos"]:
        show_user_payments(user_id)
    elif message.content_type == 'photo' or "comprovante" in text.lower():
        bot.send_message(
            user_id,
            "✅ *COMPROVANTE RECEBIDO!*\n\n"
            "Seu pagamento será verificado e em até 5 minutos você receberá o link do grupo.\n\n"
            "Aguarde! 🔥",
            parse_mode="Markdown"
        )
        
        @threaded
        def forward():
            try:
                admin_id = 123456789 
                bot.forward_message(admin_id, user_id, message.message_id)
                bot.send_message(
                    admin_id,
                    f"📸 *NOVO COMPROVANTE*\n\n"
                    f"👤 Usuário: {user_id}\n"
                    f"📱 Verificar pagamento",
                    parse_mode="Markdown"
                )
            except:
                pass
        forward()
    else:
        markup = InlineKeyboardMarkup()
        markup.row(InlineKeyboardButton("🔥 VER PLANOS", callback_data="voltar"))
        bot.send_message(user_id, "Clique para ver os planos!", reply_markup=markup)

def show_user_payments(user_id):
    """Mostra histórico de pagamentos"""
    if user_id in user_transactions and user_transactions[user_id]:
        text = "💰 *SEUS PAGAMENTOS*\n\n"
        
        for tx in user_transactions[user_id][-5:]:
            status_emoji = "✅" if tx["status"] == "COMPLETO" else "⏳"
            tx_time = datetime.fromtimestamp(tx["time"]).strftime("%d/%m %H:%M")
            text += f"{status_emoji} {tx['plano']} - R${tx['valor']:.2f} - {tx_time}\n"
        
        bot.send_message(user_id, text, parse_mode="Markdown")
    else:
        bot.send_message(user_id, "Você ainda não tem pagamentos registrados.")


@bot.message_handler(commands=['saldo'])
def cmd_saldo(message):
    user_id = message.from_user.id
    if user_id == 123456789:  
        balance = check_balance()
        if balance["success"]:
            bot.send_message(user_id, f"💰 *Saldo disponível:* R$ {balance['balance']:.2f}", parse_mode="Markdown")
        else:
            bot.send_message(user_id, f"❌ Erro: {balance.get('error')}")
    else:
        bot.send_message(user_id, "Comando restrito.")


@bot.message_handler(commands=['pendentes'])
def cmd_pendentes(message):
    user_id = message.from_user.id
    if user_id == 123456789: 
        if pending_payments:
            text = "📋 *PAGAMENTOS PENDENTES*\n\n"
            for tx_id, payment in list(pending_payments.items())[:10]:
                if payment["status"] == "PENDENTE":
                    time_passed = int((time.time() - payment["time"]) / 60)
                    text += f"🆔 {tx_id[:8]}...\n👤 {payment['user_id']}\n📦 {payment['plano']}\n⏱️ {time_passed}min\n\n"
            bot.send_message(user_id, text, parse_mode="Markdown")
        else:
            bot.send_message(user_id, "Nenhum pagamento pendente.")
    else:
        bot.send_message(user_id, "Comando restrito.")


if __name__ == "__main__":
    print("=" * 60)
    print("🤖 BOT INICIADO - COM ENVIO AUTOMÁTICO DE LINK")
    print("=" * 60)
    print(f"🔗 Link do grupo: {GRUPO_PRIVADO_LINK}")
    print(f"📁 Vídeo mensal: {VIDEOS['mensal']}")
    print("\n📁 Verificando vídeos:")
    
    for key, path in VIDEOS.items():
        if os.path.exists(path):
            print(f"✅ {key}: OK")
        else:
            print(f"⚠️ {key}: Arquivo não encontrado")
    
    print("\n⚡ Monitorando pagamentos...")
    print("✅ Aguardando mensagens...")
    print("=" * 60)
    
    try:
        bot.infinity_polling(timeout=30, long_polling_timeout=30)
    except Exception as e:
        print(f"❌ Erro: {e}")
        time.sleep(3)
        bot.infinity_polling(timeout=30, long_polling_timeout=30)