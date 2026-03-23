# 🫀 Curso ECG Clínico — Módulo 1

**Interpretación de ECG para profesionales de salud**  
Módulo 1 de 8 · 2 horas pedagógicas · Atlas interactivo + IA

---

## 📋 Contenido del módulo

| Slide | Tema | Características |
|-------|------|-----------------|
| 1 | El papel y las ondas | Trazado real con **8 hotspots** interactivos |
| 2 | Cálculo de FC | 3 trazados anotados + calculadora interactiva |
| 3 | Método F·R·E·C | BAV completo con señalizadores |
| 4 | Atlas de patrones | 6 patrones urgentes con zonas sombreadas |
| 5 | Analizador IA | Sube foto de ECG → Claude analiza y dibuja sobre la imagen |
| 6 | Checklist | 8 pasos del protocolo clínico |
| 7 | Casos clínicos | 2 casos con retroalimentación inmediata |
| 8 | Evaluación | 5 preguntas · 80% aprobación |
| 9 | Cierre | Vista previa Módulo 2 |

---

## 🚀 Ver en vivo

👉 **[https://TU-USUARIO.github.io/ecg-curso/](https://TU-USUARIO.github.io/ecg-curso/)**

---

## 🤖 Funcionalidad IA

El módulo incluye un analizador de ECG con IA (Claude Vision API):

- Sube una foto de tu trazado ECG
- La IA detecta los picos R y los marca en la imagen
- Calcula la FC con el método **300 ÷ cuadrados grandes**
- Dibuja flechas y anotaciones directamente sobre la foto
- Identifica el ritmo, QRS y cambios del ST

> ⚠️ **La IA funciona directamente en GitHub Pages.**  
> Para integrar en Moodle con tu propia API key, usa el proxy PHP incluido.

---

## 🏗️ Estructura del repositorio

```
ecg-curso/
├── index.html          ← Módulo 1 completo (autocontenido)
├── ecg-proxy.php       ← Proxy PHP para Moodle (opcional)
└── README.md
```

---

## 📐 Usar en Moodle (iframe)

```html
<iframe
  src="https://TU-USUARIO.github.io/ecg-curso/"
  width="100%"
  height="800"
  frameborder="0"
  allow="camera; microphone"
  allowfullscreen>
</iframe>
```

---

## ⚙️ Tecnologías

- HTML5 + CSS3 + JavaScript vanilla
- SVGs inline (sin dependencias externas)
- Google Fonts (Fraunces, DM Mono, Outfit)
- Claude API (Sonnet 4.6) para análisis de imagen

---

## 📄 Licencia

Uso educativo. Desarrollado para enfermería clínica — Chile.
