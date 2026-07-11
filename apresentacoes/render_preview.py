# -*- coding: utf-8 -*-
"""Renderiza um PPTX para PNGs lendo formas/cores/textos reais do arquivo.
Verificação visual (não substitui o PowerPoint, mas mostra a composição)."""
import sys, math
from PIL import Image, ImageDraw, ImageFont
from pptx import Presentation
from pptx.util import Emu
from pptx.enum.shapes import MSO_SHAPE_TYPE

EMU=914400
SCALE=110  # px por polegada
FONT="/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"
FONTB="/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"
EMOJI="/usr/share/fonts/truetype/noto/NotoColorEmoji.ttf"

def px(v): return int((v or 0)/EMU*SCALE)
def font(sz, bold):
    try: return ImageFont.truetype(FONTB if bold else FONT, max(8,int(sz*SCALE/72)))
    except: return ImageFont.load_default()
def emoji_font(sz):
    # NotoColorEmoji só existe em 109px; PIL escala via embedded bitmap
    try: return ImageFont.truetype(EMOJI, 109)
    except: return None

def rgb(shape):
    try:
        f=shape.fill
        if f.type==1: c=f.fore_color.rgb; return (c[0],c[1],c[2])
    except: pass
    return None

def grad(shape):
    try:
        f=shape.fill
        if f.type==3:
            gs=f.gradient_stops
            c0=gs[0].color.rgb; c1=gs[1].color.rgb
            return ((c0[0],c0[1],c0[2]),(c1[0],c1[1],c1[2]))
    except: pass
    return None

def is_emoji(ch):
    o=ord(ch); return o>0x1F000 or (0x2190<=o<=0x2BFF) or (0x2600<=o<=0x27BF) or o in (0x2665,0x2660)

def draw_text(img, draw, box, tf, bold_default=False):
    x,y,w,h=box
    for para in tf.paragraphs:
        runs=para.runs
        if not runs:
            y+=int(14*SCALE/72); continue
        # tamanho/cor do primeiro run
        r0=runs[0]
        sz=r0.font.size.pt if r0.font.size else 14
        bold=bool(r0.font.bold) or bold_default
        col=(30,30,46)
        try:
            if r0.font.color and r0.font.color.type is not None:
                c=r0.font.color.rgb; col=(c[0],c[1],c[2])
        except: pass
        txt="".join(rn.text for rn in runs)
        al=para.alignment
        fnt=font(sz,bold)
        # separa emoji de texto para desenhar cada um
        cx=x
        line_w=draw.textlength(txt,font=fnt)
        if al==2: cx=x+(w-line_w)  # right
        elif al==1 or (al is None and False): pass
        if str(al)=='PP_ALIGN.CENTER (2)': pass
        from pptx.enum.text import PP_ALIGN
        if al==PP_ALIGN.CENTER: cx=x+(w-line_w)//2
        elif al==PP_ALIGN.RIGHT: cx=x+(w-line_w)
        ex=cx
        efnt=emoji_font(sz)
        for ch in txt:
            if is_emoji(ch) and efnt:
                em=int(sz*SCALE/72*1.2)
                try:
                    draw.text((ex,y),ch,font=efnt,embedded_color=True)
                    ex+=em+2
                except: ex+=em
            else:
                draw.text((ex,y),ch,font=fnt,fill=col)
                ex+=draw.textlength(ch,font=fnt)
        y+=int(sz*SCALE/72*1.25*(txt.count(chr(10))+1))+2

def render(path,out_prefix):
    prs=Presentation(path)
    W=px(prs.slide_width); H=px(prs.slide_height)
    n=0
    for sl in prs.slides:
        n+=1
        img=Image.new("RGB",(W,H),(255,255,255))
        d=ImageDraw.Draw(img)
        # fundo
        try:
            bg=sl.background.fill
            if bg.type==1:
                c=bg.fore_color.rgb; img.paste((c[0],c[1],c[2]),(0,0,W,H))
        except: pass
        for sh in sl.shapes:
            x,y,w,h=px(sh.left),px(sh.top),px(sh.width),px(sh.height)
            g=grad(sh); solid=rgb(sh)
            if sh.shape_type==MSO_SHAPE_TYPE.AUTO_SHAPE or solid or g:
                oval = False
                try: oval = 'OVAL' in str(sh.auto_shape_type)
                except: pass
                if g:
                    for i in range(max(1,w)):
                        t=i/max(1,w); c=tuple(int(g[0][k]+(g[1][k]-g[0][k])*t) for k in range(3))
                        d.line([(x+i,y),(x+i,y+h)],fill=c)
                elif solid:
                    if oval: d.ellipse([x,y,x+w,y+h],fill=solid)
                    else: d.rounded_rectangle([x,y,x+w,y+h],radius=min(18,h//3),fill=solid)
            if sh.has_text_frame and sh.text_frame.text.strip():
                pad=6
                draw_text(img,d,(x+pad,y+pad,w-2*pad,h-2*pad),sh.text_frame)
        img.save(f"{out_prefix}_{n:02d}.png")
    print(f"{n} PNGs -> {out_prefix}_*.png")

render(sys.argv[1], sys.argv[2])
