# Mesa Hermética

Site institucional da **Mesa Radiônica Hermética**, terapia holística de harmonização energética. O projeto apresenta os serviços, explica o método de atendimento e disponibiliza formulário de contato com backend em PHP.

---

## Estrutura do Projeto

```
mesahermetica/
├── index.html            # Landing page principal
├── contato.html          # Página de contato com formulário
├── css/
│   ├── style.css         # Estilos globais
│   └── contato.css       # Estilos exclusivos da página de contato
├── js/
│   ├── main.js           # Navegação, animações e scroll
│   └── contact.js        # Validação e envio do formulário
├── api/
│   ├── contact.php       # Backend do formulário (SMTP / mail)
│   └── env.php           # Carregador de variáveis de ambiente
├── assets/
│   └── img/              # Imagens e logo
├── .env.example          # Modelo de variáveis de ambiente
├── .gitignore            # Arquivos ignorados pelo Git
└── README.md
```

## Tecnologias

| Camada      | Tecnologia                              |
| ----------- | --------------------------------------- |
| Markup      | HTML5 semântico, Schema.org             |
| Estilos     | CSS3 (custom properties, grid, flexbox) |
| Scripts     | JavaScript vanilla (ES6+)               |
| Ícones      | Font Awesome 6.5.1 (CDN)               |
| Tipografia  | Google Fonts — Cinzel + Montserrat      |
| Backend     | PHP 8+ (formulário de contato)          |

## Funcionalidades

- **Design responsivo** — adaptado para mobile, tablet e desktop (breakpoints: 480 px, 768 px, 1024 px)
- **Seções da landing page** — Hero, Como Funciona (timeline 3 etapas), Para Quem É, Benefícios, Sobre a Terapeuta, CTA e Aviso Legal
- **Página de contato** — formulário com validação em tempo real, máscara de telefone e FAQ
- **Backend PHP** — envio via SMTP com fallback para `mail()` e gravação local em arquivo; rate limiting por IP e honeypot anti-spam
- **Acessibilidade** — ARIA labels, navegação por teclado, contraste adequado
- **Performance** — fontes com `display=swap`, preconnect, lazy loading de imagens, CSS/JS otimizados

## Configuração Local

1. Clone o repositório:

```bash
git clone https://github.com/guilherme-ufscar/mesahermetica.git
cd mesahermetica
```

2. Copie o arquivo de ambiente e preencha com seus dados:

```bash
cp .env.example .env
```

3. Edite o `.env` com as credenciais SMTP e configurações do site.

4. Para visualizar o front-end, basta abrir `index.html` no navegador ou usar qualquer servidor estático:

```bash
# Exemplo com PHP
php -S localhost:8000

# Exemplo com Python
python -m http.server 8000
```

5. Para o formulário de contato funcionar, é necessário um servidor com PHP 8+ (Apache, Nginx, etc.).

## Variáveis de Ambiente

| Variável          | Descrição                               |
| ----------------- | --------------------------------------- |
| `SMTP_HOST`       | Servidor SMTP (ex: smtp.gmail.com)      |
| `SMTP_PORT`       | Porta SMTP (587 para TLS)               |
| `SMTP_SECURE`     | Tipo de segurança (tls/ssl)             |
| `SMTP_USER`       | Usuário de autenticação SMTP            |
| `SMTP_PASS`       | Senha ou senha de app SMTP              |
| `MAIL_TO`         | E-mail que receberá as mensagens        |
| `MAIL_FROM_NAME`  | Nome do remetente                       |
| `MAIL_FROM_EMAIL` | E-mail do remetente                     |
| `SITE_URL`        | URL de produção do site                 |
| `SITE_NAME`       | Nome exibido no site                    |
| `ALLOWED_ORIGINS` | Domínios permitidos (CORS)              |
| `RATE_LIMIT`      | Máximo de envios por IP por hora        |

## Personalização

- **Logo** — substitua `assets/img/logo.webp` pela imagem da marca
- **Imagens** — os placeholders ("Sua imagem aqui") devem ser trocados por fotos reais nas seções Hero e Para Quem É
- **WhatsApp** — altere o número `5500000000000` nos links de WhatsApp para o número real
- **Cores** — as variáveis CSS ficam no início de `css/style.css` (`:root`)

## Licença

Todos os direitos reservados © 2026
