---
title: Send your site's email through Resend
tags: email, resend, mail provider, smtp, sending, deliverability
page: admin/settings/resend
---

**Resend** sends your site's email, such as order confirmations and sign-in codes, through Resend's service instead of SMTP. It is useful where your hosting blocks outbound SMTP.

## Set it up

1. In your Resend account, get the details below.
2. Open **Resend Settings** and enter your **API Key**.
3. Press **Save Settings**.
4. Open the **Email** settings page and choose **Resend** as the **Provider**. The **From Address** and the **Send test email to** button are on that page.

Resend only sends from a verified domain, so the From address must be on a domain you have verified in your Resend account.

Use **Run diagnostics** on the Email settings page if mail does not arrive.
