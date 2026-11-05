{{--
    The data-export email. Says what it is, and says when the link dies —
    a link that silently stops working is worse than one that warned you.
--}}
<x-mail::message>
# Hello {{ $firstName }} 👋

Your copy of everything you have put into {{ $product }} is ready 📦

<x-mail::button :url="$url">
Download your data
</x-mail::button>

The link works for {{ $ttlHours }} hours, then it stops — ask for another any time ⏳

It holds what you added, and the shared things you could already see. Anything
your partner kept private stays private 🔒

Thanks,<br>
The {{ $product }} team
</x-mail::message>
