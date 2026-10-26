# Yolber Sport

Tienda de ropa y calzado deportivo — frontend TanStack Start + backend PHP/MySQL.

## Desarrollo

Necesitas Node.js, npm y (para el admin/API) Apache/PHP + MySQL.

```sh
npm install
npm run dev
```

La tienda corre en `http://localhost:8080`.  
El backend PHP debe estar en `http://localhost/backend` (o la URL de `VITE_API_URL` en `.env`).

### Variables de entorno (`.env`)

```env
VITE_API_URL=http://localhost/backend
VITE_USE_MOCK=false
VITE_WHATSAPP_PHONE=573000000000
```

### Admin

- URL: `http://localhost/backend/admin/login.php`
- Tras el primer setup: `admin@yolbersport.com` / cambia la contraseña en producción

## Scripts

| Comando | Descripción |
|---------|-------------|
| `npm run dev` | Servidor de desarrollo |
| `npm run build` | Build de producción |
| `npm run preview` | Previsualizar el build |
| `npm run lint` | ESLint |
| `npm test` | Tests |

## Stack

- TanStack Start / Router / Query
- React 19 + TypeScript
- Tailwind CSS
- PHP + MySQL (API y panel admin)
- Checkout por WhatsApp
