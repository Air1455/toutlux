export default {
    contact: {
        title: "Contacter le propriétaire",
        propertyOwner: "Propriétaire",
        verified: "Vérifié",
        success: "Message envoyé",
        messageSent: "Votre message a bien été envoyé. Le propriétaire sera notifié et pourra vous répondre via TOUTLUX.",
        sendError: "Une erreur est survenue lors de l'envoi du message.",
        send: "Envoyer le message",
        disclaimer: "Votre message sera transmis au propriétaire via TOUTLUX. Vous recevrez une notification lors de sa réponse. Aucune information personnelle ne sera partagée directement.",
        unverified: "Non vérifié",
        aboutProperty: "À propos de cette propriété",
        sendMessage: "Envoyer un message",
        messageType: "Type de message",
        subject: "Sujet",
        message: "Message",
        privacyNote: "Vos informations personnelles ne seront partagées avec le vendeur que pour cette propriété.",
        subjectPlaceholder: "Entrez le sujet de votre message",
        messagePlaceholder: "Écrivez votre message ici...",
        types: {
            visit_request: "Demande de visite",
            info_request: "Demande d'informations",
            price_negotiation: "Négociation de prix",
            other: "Autre"
        },

        form: {
            messageType: "Type de demande",
            subject: "Objet",
            subjectPlaceholder: "Objet de votre message",
            message: "Message",
            messagePlaceholder: "Votre message détaillé...",
            phoneNumber: "Numéro de téléphone",
            phoneNumberPlaceholder: "Votre numéro (optionnel)"
        },

        subjects: {
            visit: "Demande de visite - {{property}}",
            info: "Demande d'informations - {{property}}",
            negotiation: "Proposition de prix - {{property}}",
            default: "Concernant votre annonce"
        },

        templates: {
            visit: "Bonjour,\n\nJe suis intéressé(e) par votre bien « {{property}} » situé à {{city}}.\n\nSeriez-vous disponible pour organiser une visite ? Je peux m'adapter à vos disponibilités en semaine ou en week-end.\n\nCordialement,",
            info: "Bonjour,\n\nJe suis intéressé(e) par votre annonce « {{property}} ».\n\nPourriez-vous me fournir des informations complémentaires ?\n\nMerci d'avance pour votre réponse.\n\nCordialement,",
            negotiation: "Bonjour,\n\nJe suis très intéressé(e) par votre bien « {{property}} » affiché à {{price}}.\n\nSeriez-vous ouvert(e) à une négociation ?\n\nDans l'attente de votre retour.\n\nCordialement,"
        },
    }
}