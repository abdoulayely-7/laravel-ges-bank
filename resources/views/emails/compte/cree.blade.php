<x-mail::message>
    # Félicitations {{ $compte->client->user->name }} 🎉

    Votre compte a été créé avec succès.

    **Numéro du compte :** {{ $compte->numero_compte }}
    **Type de compte :** {{ ucfirst($compte->type) }}
    **Devise :** {{ $compte->devise }}
{{--    **Solde initial :** {{ number_format($compte->solde, 0, ',', ' ') }} {{ $compte->devise }}--}}
    **Votre mot de passe temporaire :** {{ $plainPassword }}

    {{-- <x-mail::button :url="route('comptes.show', $compte->id)">
        Consulter mon compte
    </x-mail::button> --}}

    Merci pour votre confiance,<br>
</x-mail::message>
