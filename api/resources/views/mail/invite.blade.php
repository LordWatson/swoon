{{--
    The invite email. Brand voice: second person, British, short sentences, one
    emoji at the end of each (docs/BRAND.md § 2).

    The code is shown as well as the link, because a link is useless when the
    two of you are in the same room and one phone is flat.
--}}
<x-mail::message>
# {{ $firstName }} would like to share a space with you 👋

@if ($isReminder)
Just a nudge — this one is still waiting 💞
@else
{{ $firstName }} has started a private space for the two of you on {{ $product }} 💞
@endif

It is only ever the two of you in there. No feed, no followers 🔒

<x-mail::button :url="$link">
Join {{ $firstName }}
</x-mail::button>

Or type this code into the app: **{{ $code }}**

The code works for 14 days ⏳

Thanks,<br>
The {{ $product }} team
</x-mail::message>
