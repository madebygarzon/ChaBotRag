# AI Chatbot RAG - WordPress Plugin

Plugin profesional de WordPress que implementa un chatbot de atención al usuario basado en RAG (Retrieval-Augmented Generation) usando DeepSeek como modelo de lenguaje.

## Características Principales

### ✅ Sistema RAG Completo
- Indexación automática de contenido de WordPress
- Búsqueda contextual basada en el contenido del sitio
- Respuestas basadas SOLO en información del sitio (no inventa)
- Arquitectura preparada para migrar a vector databases externos

### ✅ Indexación Inteligente
- Posts, Pages, Custom Post Types
- Productos WooCommerce (si está instalado)
- Campos ACF (si están disponibles)
- Limpieza automática de HTML, shortcodes y scripts
- División en chunks con overlap configurable

### ✅ Integración con DeepSeek
- Modelo: `deepseek-chat`
- Manejo de errores y rate limiting
- Reintentos automáticos con exponential backoff
- Control de temperatura y tokens

### ✅ Prompt Engineering
- Sistema de prompts configurable
- Historial de conversación (últimos N mensajes)
- Mensaje personalizable cuando no hay contexto
- Fuentes citadas en las respuestas

### ✅ Interfaz de Usuario
- Shortcode `[ai_chatbot]` para insertar en cualquier página
- Diseño responsive y moderno
- Indicador de escritura
- Fuentes clickeables
- Widget flotante (preparado para futuras versiones)

### ✅ Panel de Administración
- Dashboard con estadísticas
- Configuración completa
- Indexación con un clic
- Visualización de contenido indexado

## Requisitos

- WordPress 6.0+
- PHP 8.0+
- MySQL 5.7+ o MariaDB 10.2+
- API Key de DeepSeek ([obtener aquí](https://platform.deepseek.com/))

## Instalación

### Método 1: Upload Manual

1. Descarga el plugin como ZIP
2. Ve a WordPress Admin → Plugins → Add New → Upload Plugin
3. Selecciona el archivo ZIP y haz clic en "Install Now"
4. Activa el plugin

### Método 2: FTP

1. Descarga y descomprime el plugin
2. Sube la carpeta `ai-chatbot-rag` a `/wp-content/plugins/`
3. Activa el plugin desde el panel de WordPress

## Configuración Rápida

### 1. Configurar DeepSeek API

1. Ve a **AI Chatbot → Settings**
2. Ingresa tu API Key de DeepSeek
3. Configura los parámetros del modelo:
   - **Temperature**: 0.3 (recomendado para respuestas precisas)
   - **Max Tokens**: 1000
   - **Model**: deepseek-chat

### 2. Indexar Contenido

1. Ve a **AI Chatbot → Indexing**
2. Haz clic en "Start Indexing"
3. Espera a que termine el proceso
4. Verifica las estadísticas

### 3. Agregar Chatbot

Usa el shortcode en cualquier página o post:

```
[ai_chatbot]
```

Con parámetros opcionales:

```
[ai_chatbot title="¿En qué puedo ayudarte?" height="600px"]
```

## Configuración Avanzada

### Parámetros de Indexación

- **Chunk Size**: Tamaño de cada fragmento de texto (recomendado: 500 palabras)
- **Chunk Overlap**: Palabras que se solapan entre chunks (recomendado: 50)
- **Post Types**: Tipos de contenido a indexar (posts, pages, products, etc.)
- **Exclude Categories**: Categorías a excluir de la indexación

### Parámetros RAG

- **Max Context Chunks**: Número máximo de fragmentos a incluir en el contexto (recomendado: 5)
- **Conversation History**: Mensajes previos a incluir (recomendado: 5)
- **System Prompt**: Prompt del sistema (usa `{context}` como placeholder)
- **No Context Message**: Mensaje cuando no hay información relevante

### System Prompt por Defecto

```
Eres un asistente virtual profesional y útil del sitio web.

REGLAS ESTRICTAS:
1. SOLO puedes responder usando la información proporcionada en el CONTEXTO a continuación.
2. Si la información NO está en el contexto, debes responder: "Lo siento, no tengo información sobre ese tema en nuestra base de conocimientos."
3. NO inventes, asumas o elabores información que no esté explícitamente en el contexto.
4. Sé claro, conciso y profesional.
5. Si el usuario pregunta algo fuera del contexto, admítelo honestamente.

CONTEXTO:
{context}

Responde de manera útil y profesional basándote ÚNICAMENTE en el contexto proporcionado.
```

## Arquitectura Técnica

### Estructura de Directorios

```
ai-chatbot-rag/
├── ai-chatbot-rag.php          # Plugin principal
├── includes/
│   ├── class-database.php       # Gestión de BD
│   ├── class-activator.php      # Activación del plugin
│   ├── class-deactivator.php    # Desactivación
│   └── services/
│       ├── class-content-indexer.php     # Indexación de contenido
│       ├── class-embeddings-service.php  # Servicio de embeddings
│       ├── class-deepseek-client.php     # Cliente API DeepSeek
│       └── class-rag-engine.php          # Motor RAG
├── admin/
│   ├── class-admin.php          # Interfaz admin
│   ├── class-settings.php       # Configuración
│   └── views/                   # Vistas del admin
├── public/
│   ├── class-chatbot.php        # Widget/Shortcode
│   ├── class-rest-api.php       # API REST
│   └── views/                   # Vistas públicas
└── assets/
    ├── css/                     # Estilos
    └── js/                      # JavaScript
```

### Base de Datos

El plugin crea 3 tablas:

1. **wp_ai_chatbot_chunks**: Almacena fragmentos de contenido
   - `id`, `post_id`, `post_type`, `chunk_index`
   - `content`, `content_clean`, `content_hash`
   - `word_count`, `metadata`

2. **wp_ai_chatbot_embeddings**: Almacena embeddings (preparado para vector DB)
   - `id`, `chunk_id`, `embedding_model`
   - `embedding`, `dimensions`

3. **wp_ai_chatbot_conversations**: Historial de conversaciones
   - `id`, `session_id`, `user_id`
   - `role`, `message`, `metadata`

### REST API Endpoints

- `POST /wp-json/ai-chatbot-rag/v1/chat`
  - Envía mensaje y recibe respuesta
  - Requiere: `message`, `session_id`
  - Rate limit: 10 req/min por IP

- `GET /wp-json/ai-chatbot-rag/v1/health`
  - Verifica estado del sistema

- `GET /wp-json/ai-chatbot-rag/v1/stats` (admin only)
  - Estadísticas del sistema

## Búsqueda Contextual (MVP)

La versión MVP usa búsqueda basada en palabras clave con TF-IDF:

1. Extrae keywords del query del usuario
2. Busca chunks que contengan esas keywords
3. Calcula score de relevancia
4. Retorna los top N chunks más relevantes

**Próximas versiones**: Migración a embeddings vectoriales con:
- OpenAI Embeddings
- Pinecone / Weaviate / Qdrant
- Búsqueda por similitud de coseno

## Seguridad

### Implementado

✅ Nonces para todas las peticiones AJAX
✅ Sanitización de inputs
✅ Validación de permisos
✅ Rate limiting por IP
✅ Prepared statements SQL
✅ Escape de outputs
✅ HTTPOnly cookies

### Recomendaciones

- Mantén tu API Key segura
- No compartas credenciales en repositorios públicos
- Monitorea el uso de la API
- Limita el acceso a endpoints sensibles

## Personalización

### Modificar Estilos

Edita `assets/css/chatbot.css` o agrega CSS personalizado:

```css
.ai-chatbot-container {
    /* Tu CSS aquí */
}
```

### Modificar Prompts

Desde **AI Chatbot → Settings → RAG Settings**, puedes personalizar:
- System Prompt
- No Context Message
- Chatbot Title
- Input Placeholder

### Hooks Disponibles (Próximamente)

```php
// Filtrar contenido antes de indexar
add_filter('ai_chatbot_rag_before_index', function($content, $post_id) {
    // Modificar contenido
    return $content;
}, 10, 2);

// Modificar respuesta antes de enviar
add_filter('ai_chatbot_rag_response', function($response, $query) {
    // Modificar respuesta
    return $response;
}, 10, 2);
```

## Troubleshooting

### El chatbot no responde

1. Verifica que la API Key esté configurada
2. Revisa que el contenido esté indexado
3. Chequea el navegador console para errores JavaScript
4. Verifica que el rate limit no esté bloqueando

### La indexación falla

1. Aumenta el límite de memoria PHP (`memory_limit` en php.ini)
2. Aumenta el tiempo de ejecución (`max_execution_time`)
3. Revisa los logs de WordPress
4. Indexa por lotes si tienes mucho contenido

### Las respuestas no son relevantes

1. Ajusta el tamaño de chunks
2. Aumenta el número de chunks en contexto
3. Mejora el system prompt
4. Revisa que el contenido esté correctamente indexado

### Errores de API

1. Verifica que la API Key sea válida
2. Chequea el límite de tu plan DeepSeek
3. Revisa los logs (`error_log`)
4. Ajusta el timeout si la conexión es lenta

## Roadmap

### v1.1
- [ ] Widget flotante configurable
- [ ] Exportar conversaciones
- [ ] Métricas y analytics
- [ ] Feedback de usuarios

### v1.2
- [ ] Embeddings vectoriales reales
- [ ] Integración con Pinecone/Weaviate
- [ ] Multi-idioma mejorado
- [ ] A/B testing de prompts

### v2.0
- [ ] Múltiples modelos (OpenAI, Claude, etc.)
- [ ] Fine-tuning personalizado
- [ ] Integraciones con CRM
- [ ] API pública para desarrolladores

## Contribuir

## Promt de ejemplo de personalización
-You are a professional virtual assistant for Partner in Publishing, a publishing services company.

  YOUR ROLE:
  - Help visitors understand our services and capabilities
  - Provide accurate information based solely on the context below
  - Guide visitors to contact us for personalized assistance

  STRICT RULES:
  1. ONLY answer using information from the CONTEXT below
  2. DO NOT invent, assume, or add information not in the context
  3. If the answer is NOT in the context, politely invite the visitor to contact us
  4. Always maintain a professional, helpful, and encouraging tone
  5. When appropriate, encourage visitors to reach out for personalized quotes or consultations

  CONTEXT:
  {context}

  CONTACT INVITATION:
  When you don't have specific information, or when the visitor's needs require personalized attention, use this response format:
  "I'd be happy to help you with that! For personalized assistance with [topic], I invite you to contact our team directly. You can reach us through our contact form:
  https://partnerinpublishing.com/#brxe-8292d9

  Our team will get back to you promptly to discuss your specific needs."

  Remember: Your goal is to be helpful, informative, and guide visitors toward contacting us when needed.

  📋 How to implement it:

  1. Go to WordPress Admin → AI Chatbot → Settings
  2. Find the "System Prompt Template" field
  3. Delete the current content
  4. Paste this new prompt
  5. Click "Save Changes"
  

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## Licencia

GPL v2 o posterior

## Soporte

- Issues: [GitHub Issues](https://github.com/yourname/ai-chatbot-rag/issues)
- Documentación: [Wiki](https://github.com/yourname/ai-chatbot-rag/wiki)
- Email: support@yoursite.com

## Créditos

Desarrollado con ❤️ usando:
- WordPress
- DeepSeek AI
- PHP 8+
- Vanilla JavaScript

---

**Nota**: Este es un MVP (Minimum Viable Product). La arquitectura está preparada para escalar a soluciones enterprise con vector databases y embeddings avanzados.
