{{--
    The brand voice applies to email exactly as it does in the app: second
    person, British spelling, short sentences, one emoji at the end of each
    (docs/BRAND.md § voice). The shell around this body is Laravel's own
    markdown mail theme, which reads the product name from APP_NAME — and
    APP_NAME follows SWOON_NAME, so there is one name to change.
--}}
<x-mail::message>
# Hello from {{ $product }} 👋

Your email transport works. 🎉

Nothing here needs you — a developer sent this to check the plumbing. 🛠️

Next time you hear from us, it will be about you two. 💞

Thanks,<br>
The {{ $product }} team
</x-mail::message>
