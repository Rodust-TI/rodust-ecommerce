# Webhooks - Explicação dos Endpoints

## Diferença entre OAuth Callback e Webhook

### 🔐 OAuth Callback (`/api/melhor-envio/oauth/callback`)

**O que é:**
- Endpoint usado **apenas uma vez** durante a configuração inicial do OAuth do Melhor Envio
- É um fluxo **manual** de autenticação

**Quando é usado:**
1. Você executa o comando: `php artisan melhorenvio:start-oauth`
2. O sistema gera uma URL de autorização
3. Você acessa essa URL no navegador
4. O Melhor Envio pede autorização
5. Após autorizar, o Melhor Envio redireciona para o callback
6. O Laravel recebe o código e troca por tokens de acesso
7. Os tokens são salvos no banco de dados

**Por que precisa do UltraHook:**
- O Melhor Envio precisa redirecionar para uma URL pública
- Em desenvolvimento local, usamos UltraHook para expor o localhost

**Precisa de console no dashboard?**
- ❌ **NÃO** - É um evento único e manual
- Você sabe quando está acontecendo (você iniciou o processo)
- Não precisa de monitoramento em tempo real

### 📡 Webhook (`/api/melhor-envio/webhook`)

**O que é:**
- Endpoint usado **continuamente** para receber notificações automáticas
- O Melhor Envio envia eventos automaticamente quando algo acontece

**Quando é usado:**
- Quando um envio é criado (`order.created`)
- Quando uma etiqueta é gerada (`order.generated`)
- Quando um envio é postado (`order.posted`)
- Quando um envio é entregue (`order.delivered`)
- Quando um envio é cancelado (`order.canceled`)

**Por que precisa do UltraHook:**
- O Melhor Envio precisa enviar notificações para uma URL pública
- Em desenvolvimento local, usamos UltraHook para expor o localhost

**Precisa de console no dashboard?**
- ✅ **SIM** - É um evento automático e contínuo
- Você não sabe quando vai acontecer
- Precisa de monitoramento em tempo real para debug

## Resumo

| Tipo | Frequência | Monitoramento | Console no Dashboard |
|------|-----------|---------------|---------------------|
| OAuth Callback | Uma vez (configuração) | Manual | ❌ Não precisa |
| Webhook | Contínuo (eventos) | Automático | ✅ Sim, precisa |

## Configuração no UltraHook

Atualmente temos 4 túneis configurados:

1. **Mercado Pago Webhook** - ✅ Monitorado no dashboard
2. **Bling Webhook** - ✅ Monitorado no dashboard
3. **Melhor Envio OAuth Callback** - ❌ Não precisa de console (uso único)
4. **Melhor Envio Webhook** - ✅ Monitorado no dashboard (adicionado agora)

## Recomendação

O **OAuth Callback** pode ser removido do UltraHook se você já configurou o OAuth e não precisa reautenticar frequentemente. Mas é útil mantê-lo caso precise reautenticar no futuro.

