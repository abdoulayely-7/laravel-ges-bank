<x-mail::message>
    # Félicitations {{ $compte->client->user->name }} 🎉

    Votre compte a été créé avec succès.

    **Numéro du compte :** {{ $compte->numero_compte }}
    **Type de compte :** {{ ucfirst($compte->type) }}
    **Devise :** {{ $compte->devise }}
    **Solde :** {{ $compte->solde }}

    {{-- <x-mail::button :url="route('comptes.show', $compte->id)">
        Consulter mon compte
    </x-mail::button> --}}

    Merci pour votre confiance,<br>
</x-mail::message>
