export default {
    validation: {
        firstName: {
            required: "Le prénom est obligatoire"
        },
        lastName: {
            required: "Le nom de famille est obligatoire"
        },
        email: {
            invalid: "Le format de l'adresse email est invalide",
            required: "L'adresse email est obligatoire",
            verified: "Email vérifié",
            pending: "Email en attente de vérification",
            missing: "Adresse email manquante",
            use_google_button: "Veuillez utiliser le bouton Google pour vous connecter avec une adresse Gmail."
        },
        password: {
            required: "Le mot de passe est obligatoire"
        },
        phoneNumber: {
            invalid: "Format du numéro invalide. Veuillez saisir uniquement le numéro local sans indicatif.",
            required: "Le numéro de téléphone est obligatoire",
            verified: "Téléphone vérifié",
            pending: "Téléphone en attente de vérification",
            missing: "Numéro de téléphone manquant"
        },
        phoneNumberIndicatif: {
            invalid: "Indicatif téléphonique invalide. Il doit comporter entre 1 et 4 chiffres.",
            required: "L'indicatif téléphonique est obligatoire"
        },
        profilePicture: {
            required: "La photo de profil est obligatoire"
        },
        identityCardType: {
            required: "Le type de document d'identité est obligatoire"
        },
        identityCard: {
            required: "Le document d'identité est obligatoire",
            verified: "Identité vérifiée",
            pending: "Identité en cours de vérification",
            missing: "Documents d'identité manquants"
        },
        selfieWithId: {
            required: "Le selfie avec pièce d'identité est obligatoire"
        },
        termsAccepted: {
            required: "Vous devez accepter les conditions d'utilisation",
            verified: "Conditions d'utilisation acceptées",
            pending: "Conditions d'utilisation en attente",
            missing: "Conditions d'utilisation non acceptées"
        },
        privacyAccepted: {
            required: "Vous devez accepter la politique de confidentialité",
            verified: "Politique de confidentialité acceptée",
            pending: "Politique de confidentialité en attente",
            missing: "Politique de confidentialité non acceptée"
        },
        financial: {
            verified: "Documents financiers vérifiés",
            pending: "Documents financiers en attente",
            missing: "Documents financiers manquants"
        },
        terms: {
            accepted: "Conditions acceptées",
            pending: "Conditions en attente",
            missing: "Conditions non acceptées"
        },
        currentPassword: {
            required: "Le mot de passe actuel est obligatoire"
        },
        confirmPassword: {
            mustMatch: "Les mots de passe ne correspondent pas",
            required: "La confirmation du mot de passe est obligatoire"
        },
        verified: "Vérifié",
        pending: "En attente de vérification"
    }
};
