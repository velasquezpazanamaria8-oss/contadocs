# ContaDocs — Portal de documentos contables

Sistema SaaS para estudios contables en Perú. Permite a los contadores subir documentos (Ficha RUC, planillas, PDTs) y que sus clientes los descarguen desde un portal propio.

## Stack tecnológico
- **Framework:** Next.js 14 (App Router)
- **Base de datos:** MySQL en Hostinger + Prisma ORM
- **Estilos:** Tailwind CSS + Inter font
- **Auth:** JWT con cookies httpOnly
- **Archivos:** Guardados en /public/uploads/
- **Deploy:** Vercel (gratis)

---

## Instalación paso a paso

### 1. Base de datos en Hostinger

1. Entra a **hPanel > Bases de datos > Administrador MySQL**
2. Crea una nueva base de datos, anota: nombre, usuario y contraseña
3. Abre **phpMyAdmin**, selecciona tu base de datos
4. Ve a **SQL** y pega todo el contenido de `database.sql`
5. Ejecuta — esto crea todas las tablas y el superadmin inicial

### 2. Configurar variables de entorno

Copia `.env.example` a `.env.local` y completa:

```env
DATABASE_URL="mysql://USUARIO:PASSWORD@localhost:3306/NOMBRE_BD"
JWT_SECRET="cualquier-texto-largo-y-secreto"
NEXT_PUBLIC_SITE_URL="https://docs.tudominio.com.pe"
RESEND_API_KEY="re_xxxxxxxx"
```

El `DATABASE_URL` lo armas con los datos del paso 1.

### 3. Instalar y probar localmente

```bash
npm install
npx prisma generate
npm run dev
```

Abre http://localhost:3000/login y entra con:
- Email: `admin@contadocs.pe`
- Clave: `Admin2025#`

### 4. Deploy en Vercel

1. Sube el proyecto a GitHub
2. Entra a vercel.com y conecta el repositorio
3. En **Environment Variables** agrega las mismas variables del `.env.local`
4. Deploy automático

### 5. Conectar subdominio de Hostinger

En Vercel > tu proyecto > Settings > Domains:
- Agrega `docs.tudominio.com.pe`
- Vercel te dará un registro CNAME

En Hostinger > hPanel > DNS:
- Agrega el CNAME que te dio Vercel

---

## Usuarios y roles

| Rol | Acceso | Quién lo crea |
|-----|--------|---------------|
| `superadmin` | `/admin/*` | Script SQL inicial |
| `contador` | `/contador/*` | El superadmin desde el panel |
| `cliente` | `/cliente/*` | El contador desde su panel |

---

## Estructura de archivos PDF

Los PDFs se guardan en:
```
/public/uploads/{estudio_id}/{empresa_id}/{periodo}/{timestamp_archivo.pdf}
```

Ejemplo:
```
/public/uploads/abc123/def456/2025-06/1719234567_Ficha_RUC.pdf
```

---

## Planes y límites

| Plan | Precio | Empresas |
|------|--------|----------|
| Básico | S/ 49.90/mes | Hasta 10 |
| Profesional | S/ 99.90/mes | Hasta 25 |
| Ilimitado | S/ 200.00/mes | Sin límite |

---

## Flujo de activación (sin pasarela de pagos)

1. Contador escribe por WhatsApp con captura de su transferencia
2. Tú verificas el pago en tu banco/Yape
3. Entras al panel admin y creas el estudio con su plan
4. El sistema genera credenciales automáticamente
5. Le mandas el email y contraseña por WhatsApp
6. El contador entra y cambia su contraseña en el primer login

---

## Próximas mejoras (v2)

- [ ] Notificaciones por email al subir documentos (Resend)
- [ ] Automatización de Ficha RUC desde SUNAT
- [ ] App móvil (React Native)
- [ ] Integración Culqi para cobros automáticos
- [ ] Reportes de actividad en PDF
