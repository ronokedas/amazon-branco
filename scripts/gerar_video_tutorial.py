import os
import wave
import subprocess
from PIL import Image, ImageDraw, ImageFont

# Configurações de Vídeo
WIDTH = 1920
HEIGHT = 1080
FPS = 24

# Cores da Identidade Visual Amazon Naval
C_HEADER = "#099b70"
C_HEADER_TEXT = "#ffffff"
C_SIDEBAR = "#0f172a"
C_SIDEBAR_ACTIVE = "#099b70"
C_BG = "#f8fafc"
C_CARD_BG = "#ffffff"
C_CARD_BORDER = "#cbd5e1"
C_TEXT_MAIN = "#1e293b"
C_TEXT_MUTED = "#64748b"
C_PRIMARY = "#099b70"
C_BLUE = "#0284c7"
C_INDIGO = "#4f46e5"
C_AMBER = "#d97706"
C_ALERT_BG = "#ecfdf5"
C_ALERT_BORDER = "#a7f3d0"
C_SUBTITLE_BG = "#0f172af0"

# Fontes do Windows
def get_font(size, bold=False):
    try:
        font_name = "segoeuib.ttf" if bold else "segoeui.ttf"
        return ImageFont.truetype(f"C:/Windows/Fonts/{font_name}", size)
    except:
        try:
            font_name = "arialbd.ttf" if bold else "arial.ttf"
            return ImageFont.truetype(f"C:/Windows/Fonts/{font_name}", size)
        except:
            return ImageFont.load_default()

F_HEADER = get_font(28, bold=True)
F_TITLE = get_font(24, bold=True)
F_SECTION = get_font(20, bold=True)
F_LABEL = get_font(16, bold=True)
F_INPUT = get_font(18, bold=False)
F_HINT = get_font(14, bold=False)
F_SIDEBAR = get_font(17, bold=False)
F_SIDEBAR_ACTIVE = get_font(17, bold=True)
F_SUBTITLE = get_font(22, bold=True)
F_BADGE = get_font(13, bold=True)

def draw_sidebar(draw, active_item="Embarcações"):
    draw.rectangle([0, 70, 260, HEIGHT], fill=C_SIDEBAR)
    
    # Logo / Marca no menu
    draw.text((25, 95), "PAINEL NAVAL", fill="#94a3b8", font=get_font(14, bold=True))
    
    items = [
        ("Dashboard", 130),
        ("Clientes & Armadores", 175),
        ("Embarcações", 220),
        ("Serviços", 265),
        ("Propostas", 310),
        ("Agendamentos", 355),
        ("Vistorias de Campo", 400),
        ("Análise de Planos", 445),
        ("Protocolos SISAP", 490),
        ("Certificados", 535),
    ]
    
    for name, y in items:
        if name == active_item:
            # Pill de destaque verde
            draw.rounded_rectangle([15, y - 6, 245, y + 32], radius=8, fill=C_SIDEBAR_ACTIVE)
            draw.text((35, y), f"🚢  {name}" if name=="Embarcações" else f"•  {name}", fill="#ffffff", font=F_SIDEBAR_ACTIVE)
        else:
            draw.text((35, y), f"•  {name}", fill="#94a3b8", font=F_SIDEBAR)

def draw_header(draw):
    draw.rectangle([0, 0, WIDTH, 70], fill=C_HEADER)
    draw.text((30, 18), "⚓ SISTEMA AMAZON NAVAL", fill="#ffffff", font=F_HEADER)
    draw.text((430, 24), "|  Certificadora & Gestão Estatutária de Embarcações", fill="#d1fae5", font=get_font(18, False))
    
    # Info de usuário logado
    draw.rounded_rectangle([WIDTH - 320, 14, WIDTH - 30, 56], radius=21, fill="#047857")
    draw.text((WIDTH - 295, 23), "👤 Neto (Vistoriador Naval)  •  Online", fill="#ffffff", font=get_font(15, bold=True))

def draw_cursor(draw, pos, click_pulse=False):
    cx, cy = pos
    if click_pulse:
        # Pulso de clique amarelo
        draw.ellipse([cx - 24, cy - 24, cx + 24, cy + 24], outline="#f59e0b", width=4)
        draw.ellipse([cx - 16, cy - 16, cx + 16, cy + 16], fill="#fbbf2488")
    
    # Seta do cursor
    points = [(cx, cy), (cx + 6, cy + 22), (cx + 12, cy + 16), (cx + 22, cy + 26), (cx + 26, cy + 22), (cx + 16, cy + 12), (cx + 22, cy + 6)]
    draw.polygon(points, fill="#ffffff", outline="#0f172a")

def draw_subtitle(draw, text):
    # Faixa inferior de legenda / narração
    draw.rounded_rectangle([40, HEIGHT - 110, WIDTH - 40, HEIGHT - 25], radius=14, fill=C_SUBTITLE_BG, outline="#334155", width=2)
    draw.text((70, HEIGHT - 88), "📢  " + text, fill="#f8fafc", font=F_SUBTITLE)

def draw_card_base(draw, title="Nova Embarcação", active_tab="Dados Gerais"):
    # Card Principal
    cx1, cy1, cx2, cy2 = 290, 95, WIDTH - 40, HEIGHT - 130
    draw.rounded_rectangle([cx1, cy1, cx2, cy2], radius=14, fill=C_CARD_BG, outline=C_CARD_BORDER, width=1)
    
    # Header do Card
    draw.text((cx1 + 25, cy1 + 22), f"🚢  {title}", fill=C_HEADER, font=F_TITLE)
    
    # Banner NORMAM / Qualidade
    draw.rounded_rectangle([cx1 + 25, cy1 + 65, cx2 - 25, cy1 + 120], radius=8, fill=C_ALERT_BG, outline=C_ALERT_BORDER, width=1)
    draw.text((cx1 + 45, cy1 + 75), "Padrão Regulatório NORMAM-201 / NORMAM-202 (Autoridade Marítima)", fill=C_HEADER, font=get_font(15, True))
    draw.text((cx1 + 45, cy1 + 96), "Preencha os campos obrigatórios (*) para habilitar vistorias em campo, cálculos de arqueação e emissão de certificados estatutários.", fill="#065f46", font=F_HINT)
    
    # Tabs
    tabs = [
        ("Dados Gerais *", 0),
        ("Dados Técnicos e Propulsão", 1),
        ("Arqueação e Dimensões", 2),
        ("Linha de Carga (CNBL)", 3),
    ]
    
    tx = cx1 + 25
    ty = cy1 + 135
    for tab_name, idx in tabs:
        tw = 260
        is_active = (tab_name.split()[0] in active_tab)
        if is_active:
            draw.rounded_rectangle([tx, ty, tx + tw, ty + 38], radius=6, fill=C_HEADER)
            draw.text((tx + 20, ty + 9), tab_name, fill="#ffffff", font=get_font(15, True))
        else:
            draw.rounded_rectangle([tx, ty, tx + tw, ty + 38], radius=6, fill="#f1f5f9", outline="#e2e8f0", width=1)
            draw.text((tx + 20, ty + 9), tab_name, fill=C_TEXT_MUTED, font=get_font(15, False))
        tx += tw + 10
    
    draw.line([cx1 + 25, ty + 40, cx2 - 25, ty + 40], fill="#e2e8f0", width=2)
    return cx1, ty + 50, cx2, cy2

def draw_input(draw, x, y, w, h, label, value="", placeholder="", icon="", required=False):
    lbl = f"{icon} {label} *" if required else f"{icon} {label}"
    draw.text((x, y), lbl, fill=C_TEXT_MAIN, font=F_LABEL)
    
    # Caixa do input
    iy = y + 24
    draw.rounded_rectangle([x, iy, x + w, iy + h], radius=6, fill="#ffffff", outline=C_CARD_BORDER, width=1)
    
    if value:
        draw.text((x + 14, iy + 9), str(value), fill=C_TEXT_MAIN, font=F_INPUT)
    elif placeholder:
        draw.text((x + 14, iy + 9), str(placeholder), fill="#94a3b8", font=F_INPUT)

# RENDERIZAÇÃO DE CADA CENA
def render_scene_1(t, dur):
    # Cena 1: Abertura e Apresentação
    img = Image.new("RGB", (WIDTH, HEIGHT), "#0f172a")
    d = ImageDraw.Draw(img)
    
    # Card Central de Apresentação
    d.rounded_rectangle([320, 180, 1600, 880], radius=24, fill="#1e293b", outline="#334155", width=2)
    
    # Ícone e Logos
    d.text((960 - 280, 240), "⚓ SISTEMA AMAZON NAVAL", fill=C_HEADER, font=get_font(42, bold=True))
    d.text((960 - 330, 310), "Tutorial Operacional de Engenharia e Despacho", fill="#94a3b8", font=get_font(24, False))
    
    # Título da Aula
    d.rounded_rectangle([420, 380, 1500, 520], radius=16, fill="#099b7022", outline=C_HEADER, width=2)
    d.text((960 - 320, 410), "COMO CADASTRAR UMA EMBARCAÇÃO", fill="#34d399", font=get_font(36, bold=True))
    d.text((960 - 290, 465), "Do Cadastro Geral às Dimensões, Propulsão e Linha de Carga", fill="#ffffff", font=get_font(20, False))
    
    # Cards dos 4 Pilares Abordados
    pills = [
        ("1. Dados Gerais & Armador", 420, 560, "#0284c7"),
        ("2. Dados Técnicos & Motor", 980, 560, "#4f46e5"),
        ("3. Arqueação & Dimensões", 420, 670, "#d97706"),
        ("4. Linha de Carga NORMAM", 980, 670, "#059669")
    ]
    for ptext, px, py, pcol in pills:
        d.rounded_rectangle([px, py, px + 500, py + 80], radius=12, fill="#0f172a", outline=pcol, width=2)
        d.text((px + 25, py + 26), ptext, fill="#ffffff", font=get_font(20, bold=True))
    
    draw_subtitle(d, "Bem-vindo ao tutorial do Sistema Amazon Naval. Aprenda a cadastrar embarcações passo a passo.")
    return img

def render_scene_2(t, dur):
    # Cena 2: Listagem de Embarcações -> Clicar em "+ Nova Embarcação"
    img = Image.new("RGB", (WIDTH, HEIGHT), C_BG)
    d = ImageDraw.Draw(img)
    draw_header(d)
    draw_sidebar(d, "Embarcações")
    
    # Área principal com listagem
    d.text((290, 100), "Embarcações Cadastradas", fill=C_TEXT_MAIN, font=get_font(28, bold=True))
    d.text((290, 140), "Gerencie as balsas, empurradores, lanchas e navios cadastrados para vistoria e certificação.", fill=C_TEXT_MUTED, font=F_HINT)
    
    # Botão Nova Embarcação destacado
    btn_x, btn_y, btn_w, btn_h = WIDTH - 270, 95, 230, 48
    d.rounded_rectangle([btn_x, btn_y, btn_x + btn_w, btn_y + btn_h], radius=8, fill=C_PRIMARY)
    d.text((btn_x + 22, btn_y + 12), "+  Nova Embarcação", fill="#ffffff", font=get_font(18, bold=True))
    
    # Tabela com embarcações existentes
    d.rounded_rectangle([290, 180, WIDTH - 40, HEIGHT - 140], radius=12, fill="#ffffff", outline=C_CARD_BORDER, width=1)
    
    headers = [("NOME DA EMBARCAÇÃO", 320), ("PROPRIETÁRIO / ARMADOR", 680), ("INSCRIÇÃO CAPITANIA", 1120), ("TIPO", 1400), ("STATUS", 1680)]
    for hname, hx in headers:
        d.text((hx, 205), hname, fill=C_TEXT_MUTED, font=get_font(14, True))
    d.line([290, 235, WIDTH - 40, 235], fill="#e2e8f0", width=1)
    
    rows = [
        ("B/M TUPY EXPRESS II", "Navegação Fluvial do Norte Ltda", "911-001923/PA", "Carga Geral", "ATIVO"),
        ("BALSA GRANELEIRA RIO MAR", "Agrofluvial Grãos do Pará", "911-004412/PA", "Balsa", "ATIVO"),
        ("LANCHA RÁPIDA TAPAJÓS", "Transportes Rápidos Tapajós", "911-008129/PA", "Passageiros", "ATIVO"),
        ("EMPURRADOR CARAJÁS V", "Vale Logística Fluvial S.A.", "911-009941/PA", "Empurrador", "ATIVO"),
    ]
    ry = 260
    for r in rows:
        d.text((320, ry), r[0], fill=C_TEXT_MAIN, font=get_font(16, True))
        d.text((680, ry), r[1], fill=C_TEXT_MUTED, font=get_font(15, False))
        d.text((1120, ry), r[2], fill=C_BLUE, font=get_font(15, True))
        d.text((1400, ry), r[3], fill=C_TEXT_MAIN, font=get_font(15, False))
        d.rounded_rectangle([1680, ry - 3, 1750, ry + 22], radius=6, fill="#d1fae5")
        d.text((1692, ry), r[4], fill="#065f46", font=get_font(13, True))
        d.line([290, ry + 35, WIDTH - 40, ry + 35], fill="#f1f5f9", width=1)
        ry += 55
        
    # Animação do Cursor movendo em direção ao botão "+ Nova Embarcação"
    progress = min(1.0, t / (dur * 0.7))
    start_pos = (500, 400)
    target_pos = (btn_x + 100, btn_y + 24)
    cur_x = start_pos[0] + (target_pos[0] - start_pos[0]) * progress
    cur_y = start_pos[1] + (target_pos[1] - start_pos[1]) * progress
    
    is_clicking = (progress >= 0.9)
    draw_cursor(d, (cur_x, cur_y), click_pulse=is_clicking)
    
    draw_subtitle(d, "Passo 1: No menu lateral, acesse Embarcações e clique no botão verde 'Nova Embarcação'.")
    return img

def render_scene_3(t, dur):
    # Cena 3: Aba 1 - Dados Gerais
    img = Image.new("RGB", (WIDTH, HEIGHT), C_BG)
    d = ImageDraw.Draw(img)
    draw_header(d)
    draw_sidebar(d, "Embarcações")
    
    cx, form_y, cx2, cy2 = draw_card_base(d, "Nova Embarcação — Dados Gerais", "Dados Gerais")
    
    # Digitação progressiva dos campos
    prog = min(1.0, t / (dur * 0.8))
    
    nome_full = "B/M RIO TAPAJÓS I"
    nome_typed = nome_full[:int(len(nome_full) * min(1.0, prog * 1.5))]
    
    prop_full = "Armador Navegação Amazônia Ltda (04.123.456/0001-99)"
    prop_typed = prop_full if prog > 0.3 else ""
    
    tipo_full = "Empurrador Fluvial"
    tipo_typed = tipo_full if prog > 0.5 else ""
    
    insc_full = "911-002485/PA"
    insc_typed = insc_full if prog > 0.7 else ""
    
    porto_full = "Belém - PA"
    porto_typed = porto_full if prog > 0.8 else ""
    
    casco_full = "Aço Naval"
    casco_typed = casco_full if prog > 0.9 else ""
    
    # Coluna 1
    col1_w = (cx2 - cx - 60) // 2
    col2_x = cx + 25 + col1_w + 30
    
    draw_input(d, cx + 25, form_y + 10, col1_w, 42, "Nome da Embarcação", nome_typed, "Ex: B/M AMAZON I", "🚢", True)
    draw_input(d, cx + 25, form_y + 90, col1_w, 42, "Proprietário / Armador", prop_typed, "Selecione o cliente cadastrado...", "👤", True)
    draw_input(d, cx + 25, form_y + 170, col1_w, 42, "Tipo da Embarcação", tipo_typed, "Ex: Empurrador, Balsa, Carga...", "⚓", True)
    draw_input(d, cx + 25, form_y + 250, col1_w, 42, "Tipo de Navegação", "Interior (Área 1 e 2)" if prog > 0.85 else "", "Selecione...", "🌊", True)
    
    # Coluna 2
    draw_input(d, col2_x, form_y + 10, col1_w, 42, "Número de Inscrição na Capitania (TIE/PRPM)", insc_typed, "Ex: 911-000000/PA", "📑", True)
    draw_input(d, col2_x, form_y + 90, col1_w, 42, "Porto de Inscrição", porto_typed, "Selecione o porto...", "📍", True)
    draw_input(d, col2_x, form_y + 170, col1_w, 42, "Material do Casco", casco_typed, "Ex: Aço, Alumínio, Madeira...", "🛠️", True)
    draw_input(d, col2_x, form_y + 250, col1_w, 42, "Ano de Construção", "2024" if prog > 0.9 else "", "Ex: 2024", "📅", False)
    
    # Destaque com cursor no campo Nome
    if prog < 0.5:
        draw_cursor(d, (cx + 180, form_y + 55), click_pulse=False)
    else:
        draw_cursor(d, (col2_x + 180, form_y + 55), click_pulse=False)
        
    draw_subtitle(d, "Passo 2: Na aba Dados Gerais, informe o Nome, Armador, Inscrição na Capitania e Casco.")
    return img

def render_scene_4(t, dur):
    # Cena 4: Aba 2 - Dados Técnicos e Propulsão
    img = Image.new("RGB", (WIDTH, HEIGHT), C_BG)
    d = ImageDraw.Draw(img)
    draw_header(d)
    draw_sidebar(d, "Embarcações")
    
    cx, form_y, cx2, cy2 = draw_card_base(d, "Nova Embarcação — Dados Técnicos e Propulsão", "Dados Técnicos")
    
    prog = min(1.0, t / (dur * 0.8))
    col1_w = (cx2 - cx - 60) // 2
    col2_x = cx + 25 + col1_w + 30
    
    draw_input(d, cx + 25, form_y + 10, col1_w, 42, "Possui Propulsão?", "Sim (Embarcação Propulsada)" if prog > 0.2 else "", "Selecione...", "⚡", True)
    draw_input(d, cx + 25, form_y + 90, col1_w, 42, "Tipo de Propulsão", "Motor Diesel de Combustão Interna" if prog > 0.4 else "", "Selecione...", "⚙️", True)
    draw_input(d, cx + 25, form_y + 170, col1_w, 42, "Quantidade de Motores Principais", "2 (Dois Motores)" if prog > 0.6 else "", "Ex: 1, 2...", "🔢", True)
    draw_input(d, cx + 25, form_y + 250, col1_w, 42, "Fabricante / Marca dos Motores", "Scania / Marinizados" if prog > 0.8 else "", "Ex: Scania, Cummins, Caterpillar", "🏭", False)
    
    draw_input(d, col2_x, form_y + 10, col1_w, 42, "Potência Total (HP / BHP)", "1.200 BHP (2x 600 BHP)" if prog > 0.5 else "", "Ex: 1200", "🐎", True)
    draw_input(d, col2_x, form_y + 90, col1_w, 42, "Potência Total em Quilowatts (kW)", "882,60 kW" if prog > 0.7 else "", "Ex: 882.6", "⚡", False)
    draw_input(d, col2_x, form_y + 170, col1_w, 42, "Lotação de Tripulantes", "4 Tripulantes" if prog > 0.8 else "", "Ex: 4", "👨‍✈️", True)
    draw_input(d, col2_x, form_y + 250, col1_w, 42, "Lotação de Passageiros", "0 (Sem passageiros - Carga/Empurra)" if prog > 0.9 else "", "Ex: 0", "👥", True)
    
    draw_cursor(d, (col2_x + 160, form_y + 55), click_pulse=(prog > 0.45 and prog < 0.6))
    draw_subtitle(d, "Passo 3: Na aba Técnica, defina a propulsão, quantidade e potência dos motores e lotação.")
    return img

def render_scene_5(t, dur):
    # Cena 5: Aba 3 - Arqueação e Dimensões
    img = Image.new("RGB", (WIDTH, HEIGHT), C_BG)
    d = ImageDraw.Draw(img)
    draw_header(d)
    draw_sidebar(d, "Embarcações")
    
    cx, form_y, cx2, cy2 = draw_card_base(d, "Nova Embarcação — Arqueação e Dimensões", "Arqueação")
    
    prog = min(1.0, t / (dur * 0.8))
    col1_w = (cx2 - cx - 60) // 2
    col2_x = cx + 25 + col1_w + 30
    
    draw_input(d, cx + 25, form_y + 10, col1_w, 42, "Comprimento Total (LOA) em metros", "22,50 m" if prog > 0.2 else "", "Ex: 22.50", "📏", True)
    draw_input(d, cx + 25, form_y + 90, col1_w, 42, "Comprimento entre Perpendiculares (LPP)", "21,00 m" if prog > 0.4 else "", "Ex: 21.00", "📐", False)
    draw_input(d, cx + 25, form_y + 170, col1_w, 42, "Boca Moldada (Largura / Beam)", "7,20 m" if prog > 0.6 else "", "Ex: 7.20", "↔️", True)
    draw_input(d, cx + 25, form_y + 250, col1_w, 42, "Pontal Moldado (Altura / Depth)", "2,40 m" if prog > 0.8 else "", "Ex: 2.40", "↕️", True)
    
    draw_input(d, col2_x, form_y + 10, col1_w, 42, "Calado Máximo de Verão (Draft)", "1,60 m" if prog > 0.5 else "", "Ex: 1.60", "⚓", True)
    draw_input(d, col2_x, form_y + 90, col1_w, 42, "Arqueação Bruta (AB / Gross Tonnage)", "142,00" if prog > 0.7 else "", "Ex: 142.00", "⚖️", True)
    draw_input(d, col2_x, form_y + 170, col1_w, 42, "Arqueação Líquida (AL / Net Tonnage)", "42,00" if prog > 0.85 else "", "Ex: 42.00", "⚖️", False)
    draw_input(d, col2_x, form_y + 250, col1_w, 42, "Porte Bruto (TPB / DWT em toneladas)", "210,00 t" if prog > 0.9 else "", "Ex: 210.00", "📦", True)
    
    draw_cursor(d, (col2_x + 180, form_y + 135), click_pulse=(prog > 0.65 and prog < 0.8))
    draw_subtitle(d, "Passo 4: Informe Comprimento, Boca, Pontal, Calado e Arqueação Bruta conforme a NORMAM.")
    return img

def render_scene_6(t, dur):
    # Cena 6: Aba 4 - Linha de Carga / Borda Livre (CNBL)
    img = Image.new("RGB", (WIDTH, HEIGHT), C_BG)
    d = ImageDraw.Draw(img)
    draw_header(d)
    draw_sidebar(d, "Embarcações")
    
    cx, form_y, cx2, cy2 = draw_card_base(d, "Nova Embarcação — Linha de Carga (CNBL)", "Linha de Carga")
    
    prog = min(1.0, t / (dur * 0.8))
    col1_w = (cx2 - cx - 60) // 2
    col2_x = cx + 25 + col1_w + 30
    
    draw_input(d, cx + 25, form_y + 10, col1_w, 42, "Tipo de Borda Livre", "Tipo B (Carga / Navegação Interior)" if prog > 0.3 else "", "Selecione...", "🚢", True)
    draw_input(d, cx + 25, form_y + 90, col1_w, 42, "Borda Livre de Verão Atribuída", "800 mm" if prog > 0.6 else "", "Ex: 800", "📏", True)
    draw_input(d, cx + 25, form_y + 170, col1_w, 42, "Calado de Linha de Carga", "1,60 m" if prog > 0.8 else "", "Ex: 1.60", "⚓", True)
    
    # Ilustração do Disco de Plimsoll e Linhas de Carga
    draw_rounded = d.rounded_rectangle
    draw_rounded([col2_x, form_y + 10, col2_x + col1_w, form_y + 300], radius=10, fill="#f8fafc", outline="#cbd5e1", width=1)
    d.text((col2_x + 20, form_y + 25), "📐 Marcas Oficiais da Linha de Carga (NORMAM-202)", fill=C_HEADER, font=get_font(16, True))
    
    # Desenho do Disco
    cx_p = col2_x + 140
    cy_p = form_y + 160
    d.ellipse([cx_p - 40, cy_p - 40, cx_p + 40, cy_p + 40], outline=C_HEADER, width=4)
    d.line([cx_p - 60, cy_p, cx_p + 60, cy_p], fill=C_HEADER, width=4)
    d.text((cx_p - 75, cy_p - 12), "A", fill=C_HEADER, font=get_font(20, True))
    d.text((cx_p + 68, cy_p - 12), "N", fill=C_HEADER, font=get_font(20, True))
    
    # Linhas de carga verticais e horizontais
    lx = col2_x + 280
    d.line([lx, cy_p - 50, lx, cy_p + 50], fill="#334155", width=4)
    d.line([lx, cy_p - 30, lx + 50, cy_p - 30], fill="#334155", width=3)
    d.text((lx + 58, cy_p - 40), "AD (Água Doce)", fill="#0284c7", font=get_font(14, True))
    d.line([lx, cy_p, lx + 50, cy_p], fill="#334155", width=3)
    d.text((lx + 58, cy_p - 10), "V (Verão)", fill="#059669", font=get_font(14, True))
    
    draw_subtitle(d, "Passo 5: Na Linha de Carga, preencha a borda livre de verão para o Certificado CNBL.")
    return img

def render_scene_7(t, dur):
    # Cena 7: Clicar em "Criar Embarcação", Toast de Sucesso e Conclusão
    img = Image.new("RGB", (WIDTH, HEIGHT), C_BG)
    d = ImageDraw.Draw(img)
    draw_header(d)
    draw_sidebar(d, "Embarcações")
    
    prog = min(1.0, t / (dur * 0.7))
    
    if prog < 0.4:
        # Mostrando o formulário com o botão "Criar Embarcação" em evidência
        cx, form_y, cx2, cy2 = draw_card_base(d, "Nova Embarcação — Conclusão do Cadastro", "Dados Gerais")
        
        btn_save_x = cx2 - 250
        btn_save_y = cy2 - 70
        d.rounded_rectangle([btn_save_x, btn_save_y, btn_save_x + 230, btn_save_y + 48], radius=8, fill=C_PRIMARY)
        d.text((btn_save_x + 40, btn_save_y + 12), "💾  Criar Embarcação", fill="#ffffff", font=get_font(18, True))
        
        # Cursor clicando no botão salvar
        draw_cursor(d, (btn_save_x + 110, btn_save_y + 24), click_pulse=(prog > 0.25))
    else:
        # Tela de Sucesso / Listagem Atualizada
        d.text((290, 100), "Embarcações Cadastradas", fill=C_TEXT_MAIN, font=get_font(28, bold=True))
        
        # Toast de Sucesso em Destaque
        d.rounded_rectangle([550, 120, 1450, 190], radius=12, fill="#ecfdf5", outline="#10b981", width=2)
        d.text((580, 142), "✅ SUCESSO:", fill="#065f46", font=get_font(20, True))
        d.text((710, 143), "Embarcação 'B/M RIO TAPAJÓS I' cadastrada com sucesso no sistema!", fill="#047857", font=get_font(18, False))
        
        # Tabela com a nova embarcação no topo
        d.rounded_rectangle([290, 220, WIDTH - 40, HEIGHT - 140], radius=12, fill="#ffffff", outline=C_CARD_BORDER, width=1)
        
        headers = [("NOME DA EMBARCAÇÃO", 320), ("PROPRIETÁRIO / ARMADOR", 680), ("INSCRIÇÃO CAPITANIA", 1120), ("TIPO", 1400), ("STATUS", 1680)]
        for hname, hx in headers:
            d.text((hx, 245), hname, fill=C_TEXT_MUTED, font=get_font(14, True))
        d.line([290, 275, WIDTH - 40, 275], fill="#e2e8f0", width=1)
        
        # Linha 1: A NOVA EMBARCAÇÃO RECÉM CADASTRADA COM FUNDO VERDE CLARO
        d.rounded_rectangle([295, 285, WIDTH - 45, 335], radius=6, fill="#f0fdf4")
        d.text((320, 300), "⭐ B/M RIO TAPAJÓS I (Nova)", fill="#065f46", font=get_font(17, True))
        d.text((680, 300), "Armador Navegação Amazônia Ltda", fill=C_TEXT_MAIN, font=get_font(15, False))
        d.text((1120, 300), "911-002485/PA", fill=C_BLUE, font=get_font(15, True))
        d.text((1400, 300), "Empurrador Fluvial", fill=C_TEXT_MAIN, font=get_font(15, False))
        d.rounded_rectangle([1680, 296, 1750, 322], radius=6, fill="#d1fae5")
        d.text((1692, 301), "ATIVO", fill="#065f46", font=get_font(13, True))
        
        # Outras linhas
        rows = [
            ("B/M TUPY EXPRESS II", "Navegação Fluvial do Norte Ltda", "911-001923/PA", "Carga Geral", "ATIVO"),
            ("BALSA GRANELEIRA RIO MAR", "Agrofluvial Grãos do Pará", "911-004412/PA", "Balsa", "ATIVO"),
        ]
        ry = 355
        for r in rows:
            d.text((320, ry), r[0], fill=C_TEXT_MAIN, font=get_font(16, True))
            d.text((680, ry), r[1], fill=C_TEXT_MUTED, font=get_font(15, False))
            d.text((1120, ry), r[2], fill=C_BLUE, font=get_font(15, True))
            d.text((1400, ry), r[3], fill=C_TEXT_MAIN, font=get_font(15, False))
            d.rounded_rectangle([1680, ry - 3, 1750, ry + 22], radius=6, fill="#d1fae5")
            d.text((1692, ry), r[4], fill="#065f46", font=get_font(13, True))
            d.line([290, ry + 35, WIDTH - 40, ry + 35], fill="#f1f5f9", width=1)
            ry += 55
            
    draw_subtitle(d, "Passo 6: Clique em 'Criar Embarcação'. Cadastro concluído e pronto para vistorias!")
    return img

SCENE_RENDERERS = [
    render_scene_1,
    render_scene_2,
    render_scene_3,
    render_scene_4,
    render_scene_5,
    render_scene_6,
    render_scene_7,
]

def main():
    os.makedirs("scratch/video_clips", exist_ok=True)
    os.makedirs("docs", exist_ok=True)
    
    clip_files = []
    
    for i, renderer in enumerate(SCENE_RENDERERS, 1):
        audio_path = f"scratch/audio_tutorial/scene_{i}.wav"
        with wave.open(audio_path, 'rb') as w:
            frames_count = w.getnframes()
            rate = w.getframerate()
            duration = frames_count / float(rate)
        
        num_frames = int(duration * FPS) + 1
        print(f"\n---> Rendering Scene {i}: {duration:.2f}s ({num_frames} frames)...")
        
        frames_dir = f"scratch/video_clips/scene_{i}_frames"
        os.makedirs(frames_dir, exist_ok=True)
        
        # Renderizar cada frame da cena
        for f in range(num_frames):
            t = f / float(FPS)
            img = renderer(t, duration)
            img.save(f"{frames_dir}/frame_{f:05d}.png", quality=95)
            if f % 100 == 0 or f == num_frames - 1:
                print(f"  Frame {f}/{num_frames} rendered.")
                
        # Gerar o clipe de vídeo da cena com áudio sincronizado via FFmpeg
        clip_mp4 = f"scratch/video_clips/clip_{i}.mp4"
        cmd = [
            "ffmpeg", "-y",
            "-framerate", str(FPS),
            "-i", f"{frames_dir}/frame_%05d.png",
            "-i", audio_path,
            "-c:v", "libx264",
            "-preset", "fast",
            "-crf", "20",
            "-pix_fmt", "yuv420p",
            "-c:a", "aac",
            "-b:a", "192k",
            "-shortest",
            clip_mp4
        ]
        res = subprocess.run(cmd, capture_output=True, text=True)
        if res.returncode != 0:
            print("FFmpeg error on scene", i, res.stderr)
            return
        print(f"  Clip {i} generated: {clip_mp4}")
        clip_files.append(os.path.abspath(clip_mp4).replace('\\', '/'))
    
    # Criar arquivo de concatenação
    concat_txt = "scratch/video_clips/concat_list.txt"
    with open(concat_txt, "w", encoding="utf-8") as f:
        for c in clip_files:
            f.write(f"file '{c}'\n")
            
    output_video = os.path.abspath("docs/video_tutorial_cadastro_embarcacao.mp4").replace('\\', '/')
    print(f"\n---> Concatenating all scenes into final video: {output_video}...")
    
    concat_cmd = [
        "ffmpeg", "-y",
        "-f", "concat",
        "-safe", "0",
        "-i", concat_txt,
        "-c", "copy",
        output_video
    ]
    res = subprocess.run(concat_cmd, capture_output=True, text=True)
    if res.returncode != 0:
        print("FFmpeg concat error:", res.stderr)
        return
        
    print(f"\nSUCCESS! Video tutorial generated at: {output_video}")
    size_mb = os.path.getsize(output_video) / (1024 * 1024)
    print(f"File size: {size_mb:.2f} MB")

if __name__ == "__main__":
    main()
