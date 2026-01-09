# 🧪 Guía de Prueba del API

## 📝 Preparación

### 1. Verificar que Apache y PHP estén funcionando
```bash
sudo systemctl status apache2
php -v
```

### 2. Verificar que la base de datos existe
```bash
sudo mysql -e "SHOW DATABASES LIKE 'neon_watchdog_cms';"
sudo mysql neon_watchdog_cms -e "SHOW TABLES;"
```

---

## 🔐 Pruebas de Autenticación

### Login (Obtener Token)
```bash
curl -X POST http://localhost/app-gestion-neon-watchdogs/api/auth.php \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "admin123"
  }' | jq
```

**Respuesta esperada:**
```json
{
  "success": true,
  "message": "Login successful",
  "token": "abc123...",
  "user": {
    "id": 1,
    "username": "admin",
    "email": "admin@neonwatchdog.local",
    "full_name": "Administrador del Sistema",
    "role": "admin",
    "is_active": true
  }
}
```

**Guarda el token para las siguientes peticiones:**
```bash
export TOKEN="abc123..."
```

### Verificar Sesión
```bash
curl -X GET http://localhost/app-gestion-neon-watchdogs/api/auth.php \
  -H "Authorization: Bearer $TOKEN" | jq
```

### Logout
```bash
curl -X DELETE http://localhost/app-gestion-neon-watchdogs/api/auth.php \
  -H "Authorization: Bearer $TOKEN" | jq
```

---

## 🖥️ Pruebas de Servicios

### Listar Todos los Servicios
```bash
curl -X GET http://localhost/app-gestion-neon-watchdogs/api/services.php \
  -H "Authorization: Bearer $TOKEN" | jq
```

### Obtener Servicio Específico
```bash
curl -X GET http://localhost/app-gestion-neon-watchdogs/api/services.php/apache \
  -H "Authorization: Bearer $TOKEN" | jq
```

### Crear Nuevo Servicio
```bash
curl -X POST http://localhost/app-gestion-neon-watchdogs/api/services.php \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "redis",
    "display_name": "Redis Cache",
    "description": "Redis in-memory database",
    "enabled": true,
    "check_type": "tcp_port",
    "check_config": {
      "host": "localhost",
      "port": 6379,
      "timeout_seconds": 5
    },
    "action_type": "systemd_restart",
    "action_config": {
      "service": "redis.service"
    },
    "max_failures": 3,
    "check_interval_seconds": 30,
    "restart_delay_seconds": 5
  }' | jq
```

### Actualizar Servicio
```bash
curl -X PUT http://localhost/app-gestion-neon-watchdogs/api/services.php/apache \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "display_name": "Apache HTTP Server 2.4",
    "description": "Main web server - updated",
    "max_failures": 5,
    "check_interval_seconds": 60
  }' | jq
```

### Toggle Servicio (Habilitar/Deshabilitar)
```bash
curl -X PUT http://localhost/app-gestion-neon-watchdogs/api/services.php/nginx/toggle \
  -H "Authorization: Bearer $TOKEN" | jq
```

### Eliminar Servicio (Solo Admin)
```bash
curl -X DELETE http://localhost/app-gestion-neon-watchdogs/api/services.php/redis \
  -H "Authorization: Bearer $TOKEN" | jq
```

---

## 📊 Pruebas de Dashboard

### Obtener Estadísticas
```bash
curl -X GET http://localhost/app-gestion-neon-watchdogs/api/dashboard.php \
  -H "Authorization: Bearer $TOKEN" | jq
```

**Respuesta esperada:**
```json
{
  "success": true,
  "stats": {
    "total_services": 4,
    "active_services": 3,
    "healthy_services": 3,
    "unhealthy_services": 0,
    "restarts_today": 0,
    "unread_notifications": 1
  },
  "timestamp": "2026-01-09T22:30:00+01:00"
}
```

---

## 🧪 Script de Prueba Completo

Guarda este script como `test_api.sh` y ejecútalo:

```bash
#!/bin/bash

BASE_URL="http://localhost/app-gestion-neon-watchdogs/api"

echo "================================"
echo "🧪 TEST API - NEON WATCHDOG CMS"
echo "================================"
echo ""

# 1. Login
echo "1️⃣ Testing Login..."
LOGIN_RESPONSE=$(curl -s -X POST $BASE_URL/auth.php \
  -H "Content-Type: application/json" \
  -d '{"username": "admin", "password": "admin123"}')

TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.token')

if [ "$TOKEN" != "null" ]; then
    echo "✅ Login successful! Token: ${TOKEN:0:20}..."
else
    echo "❌ Login failed!"
    exit 1
fi
echo ""

# 2. Verificar sesión
echo "2️⃣ Testing Session Validation..."
SESSION_RESPONSE=$(curl -s -X GET $BASE_URL/auth.php \
  -H "Authorization: Bearer $TOKEN")

USERNAME=$(echo $SESSION_RESPONSE | jq -r '.session.username')

if [ "$USERNAME" == "admin" ]; then
    echo "✅ Session valid! User: $USERNAME"
else
    echo "❌ Session validation failed!"
fi
echo ""

# 3. Obtener estadísticas
echo "3️⃣ Testing Dashboard Stats..."
STATS_RESPONSE=$(curl -s -X GET $BASE_URL/dashboard.php \
  -H "Authorization: Bearer $TOKEN")

TOTAL_SERVICES=$(echo $STATS_RESPONSE | jq -r '.stats.total_services')

if [ "$TOTAL_SERVICES" != "null" ]; then
    echo "✅ Dashboard stats retrieved!"
    echo "   Total Services: $TOTAL_SERVICES"
    echo "   Active: $(echo $STATS_RESPONSE | jq -r '.stats.active_services')"
    echo "   Healthy: $(echo $STATS_RESPONSE | jq -r '.stats.healthy_services')"
else
    echo "❌ Failed to get dashboard stats!"
fi
echo ""

# 4. Listar servicios
echo "4️⃣ Testing List Services..."
SERVICES_RESPONSE=$(curl -s -X GET $BASE_URL/services.php \
  -H "Authorization: Bearer $TOKEN")

SERVICE_COUNT=$(echo $SERVICES_RESPONSE | jq -r '.targets | length')

if [ "$SERVICE_COUNT" != "null" ]; then
    echo "✅ Services listed! Count: $SERVICE_COUNT"
    echo $SERVICES_RESPONSE | jq -r '.targets[] | "   - \(.name): \(.display_name)"'
else
    echo "❌ Failed to list services!"
fi
echo ""

# 5. Obtener servicio específico
echo "5️⃣ Testing Get Specific Service..."
SERVICE_RESPONSE=$(curl -s -X GET $BASE_URL/services.php/apache \
  -H "Authorization: Bearer $TOKEN")

SERVICE_NAME=$(echo $SERVICE_RESPONSE | jq -r '.name')

if [ "$SERVICE_NAME" == "apache" ]; then
    echo "✅ Service retrieved: $(echo $SERVICE_RESPONSE | jq -r '.display_name')"
    echo "   Check Type: $(echo $SERVICE_RESPONSE | jq -r '.check_type')"
    echo "   Enabled: $(echo $SERVICE_RESPONSE | jq -r '.enabled')"
else
    echo "❌ Failed to get service!"
fi
echo ""

# 6. Crear servicio de prueba
echo "6️⃣ Testing Create Service..."
CREATE_RESPONSE=$(curl -s -X POST $BASE_URL/services.php \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "test_service",
    "display_name": "Test Service",
    "description": "Service for API testing",
    "enabled": true,
    "check_type": "tcp_port",
    "check_config": {"host": "localhost", "port": 9999, "timeout_seconds": 5},
    "action_type": "command",
    "action_config": {"cmd": "echo test"}
  }')

CREATE_STATUS=$(echo $CREATE_RESPONSE | jq -r '.message')

if [[ "$CREATE_STATUS" == *"successfully"* ]]; then
    echo "✅ Service created successfully!"
else
    echo "⚠️ Service creation: $(echo $CREATE_RESPONSE | jq -r '.error // .message')"
fi
echo ""

# 7. Toggle servicio
echo "7️⃣ Testing Toggle Service..."
TOGGLE_RESPONSE=$(curl -s -X PUT $BASE_URL/services.php/test_service/toggle \
  -H "Authorization: Bearer $TOKEN")

TOGGLE_STATUS=$(echo $TOGGLE_RESPONSE | jq -r '.message')

if [[ "$TOGGLE_STATUS" == *"successfully"* ]]; then
    echo "✅ Service toggled! New state: $(echo $TOGGLE_RESPONSE | jq -r '.enabled')"
else
    echo "❌ Toggle failed!"
fi
echo ""

# 8. Eliminar servicio
echo "8️⃣ Testing Delete Service..."
DELETE_RESPONSE=$(curl -s -X DELETE $BASE_URL/services.php/test_service \
  -H "Authorization: Bearer $TOKEN")

DELETE_STATUS=$(echo $DELETE_RESPONSE | jq -r '.message')

if [[ "$DELETE_STATUS" == *"successfully"* ]]; then
    echo "✅ Service deleted successfully!"
else
    echo "⚠️ Delete: $(echo $DELETE_RESPONSE | jq -r '.error // .message')"
fi
echo ""

# 9. Logout
echo "9️⃣ Testing Logout..."
LOGOUT_RESPONSE=$(curl -s -X DELETE $BASE_URL/auth.php \
  -H "Authorization: Bearer $TOKEN")

LOGOUT_STATUS=$(echo $LOGOUT_RESPONSE | jq -r '.message')

if [[ "$LOGOUT_STATUS" == *"successful"* ]]; then
    echo "✅ Logout successful!"
else
    echo "❌ Logout failed!"
fi
echo ""

echo "================================"
echo "✅ ALL TESTS COMPLETED!"
echo "================================"
```

**Ejecutar:**
```bash
chmod +x test_api.sh
./test_api.sh
```

---

## 🧑‍💻 Pruebas con Diferentes Roles

### Operator (Puede crear y modificar, pero no eliminar)
```bash
# Login como operator
curl -X POST http://localhost/app-gestion-neon-watchdogs/api/auth.php \
  -H "Content-Type: application/json" \
  -d '{"username": "operator", "password": "admin123"}' | jq

# Guardar token
export OPERATOR_TOKEN="..."

# Intentar eliminar (debe fallar)
curl -X DELETE http://localhost/app-gestion-neon-watchdogs/api/services.php/nginx \
  -H "Authorization: Bearer $OPERATOR_TOKEN" | jq
```

### Viewer (Solo lectura)
```bash
# Login como viewer
curl -X POST http://localhost/app-gestion-neon-watchdogs/api/auth.php \
  -H "Content-Type: application/json" \
  -d '{"username": "viewer", "password": "admin123"}' | jq

# Guardar token
export VIEWER_TOKEN="..."

# Ver servicios (debe funcionar)
curl -X GET http://localhost/app-gestion-neon-watchdogs/api/services.php \
  -H "Authorization: Bearer $VIEWER_TOKEN" | jq

# Intentar crear (debe fallar)
curl -X POST http://localhost/app-gestion-neon-watchdogs/api/services.php \
  -H "Authorization: Bearer $VIEWER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "test"}' | jq
```

---

## 🐛 Solución de Problemas

### Error 401 - Unauthorized
- Verifica que el token sea válido
- Verifica que el token no haya expirado (24 horas)
- Haz login de nuevo

### Error 403 - Forbidden
- Verifica que tu usuario tenga los permisos necesarios
- Admin: puede todo
- Operator: puede crear y modificar
- Viewer: solo lectura

### Error 500 - Internal Server Error
- Revisa los logs de Apache: `sudo tail -f /var/log/apache2/error.log`
- Verifica la conexión a la base de datos
- Verifica que PHP tenga los permisos correctos

### No se puede conectar
- Verifica que Apache esté corriendo: `sudo systemctl status apache2`
- Verifica la ruta del proyecto
- Verifica los permisos: `sudo chown -R www-data:www-data /var/www/html/app-gestion-neon-watchdogs`

---

## 📚 Referencia Rápida

### Headers Requeridos
```
Authorization: Bearer <token>
Content-Type: application/json
```

### Endpoints Disponibles
- `POST /api/auth.php` - Login
- `GET /api/auth.php` - Verificar sesión
- `DELETE /api/auth.php` - Logout
- `GET /api/dashboard.php` - Estadísticas
- `GET /api/services.php` - Listar servicios
- `GET /api/services.php/{name}` - Obtener servicio
- `POST /api/services.php` - Crear servicio
- `PUT /api/services.php/{name}` - Actualizar servicio
- `PUT /api/services.php/{name}/toggle` - Toggle servicio
- `DELETE /api/services.php/{name}` - Eliminar servicio

### Roles y Permisos
| Acción | Admin | Operator | Viewer |
|--------|-------|----------|--------|
| Ver servicios | ✅ | ✅ | ✅ |
| Crear servicios | ✅ | ✅ | ❌ |
| Modificar servicios | ✅ | ✅ | ❌ |
| Eliminar servicios | ✅ | ❌ | ❌ |
| Ver estadísticas | ✅ | ✅ | ✅ |
