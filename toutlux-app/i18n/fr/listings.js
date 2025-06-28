export default {
    listings: {
        title: "Mes annonces",
        myListings: "Mes annonces",
        createFirst: "Créez votre première annonce",
        createFirstListing: "Publiez votre première annonce pour commencer à recevoir des contacts qualifiés",
        manageAll: "Gérer toutes mes annonces",
        moreListings: "{{count}} autres annonces",
        viewAll: "Voir tout",
        noResultsFound: "Aucun résultat trouvé",
        noListingsYet: "Aucune annonce disponible pour le moment",
        tryDifferentFilters: "Essayez d'autres filtres pour affiner votre recherche",
        create: "Créer l'annonce",
        edit: "Modifier",
        delete: "Supprimer",
        deleteTitle: "Supprimer l'annonce",
        deleteConfirmation: "Confirmez-vous la suppression de l'annonce « {{title}} » ? Cette action est irréversible.",
        deleteSuccess: "Annonce supprimée avec succès.",
        deleteError: "Une erreur est survenue lors de la suppression de l'annonce.",
        deleteMultipleTitle: "Supprimer les annonces sélectionnées",
        deleteMultipleConfirmation: "Confirmez-vous la suppression de ces {{count}} annonces ? Cette action est irréversible.",
        deleteMultipleSuccess: "{{count}} annonces supprimées avec succès.",
        createSuccess: "Annonce créée avec succès.",
        createError: "Une erreur est survenue lors de la création de l'annonce.",
        updateSuccess: "Annonce mise à jour avec succès.",
        updateError: "Une erreur est survenue lors de la mise à jour de l'annonce.",
        loadingListing: "Chargement de l'annonce...",
        listingNotFound: "Annonce introuvable.",
        initializingForm: "Initialisation du formulaire...",
        editListing: "Modifier l'annonce",
        saveChanges: "Enregistrer les modifications",
        unsavedChangesTitle: "Modifications non enregistrées",
        unsavedChangesMessage: "Vous avez des modifications non enregistrées. Souhaitez-vous les abandonner ?",
        forRent: "À louer",
        forSale: "À vendre",
        maxImagesReached: "Vous avez atteint la limite de {{max}} images.",
        stepProgress: "Étape {{current}} sur {{total}}",
        totalListings: "{{count}} annonce(s) au total",
        filteredResults: "{{count}} résultat(s)",
        selectedCount: "{{count}}/{{total}} sélectionnée(s)",
        searchPlaceholder: "Recherchez dans vos annonces...",

        steps: {
            basicInfo: "Informations de base",
            details: "Détails et prix",
            location: "Localisation",
            images: "Photos"
        },

        form: {
            shortDescription: "Titre de l'annonce",
            shortDescriptionPlaceholder: "Ex: Bel appartement 3 pièces avec vue mer",
            longDescription: "Description détaillée",
            longDescriptionPlaceholder: "Décrivez votre bien en détail : caractéristiques, équipements, environnement...",
            propertyType: "Type de bien",
            listingType: "Type d'annonce",
            price: "Prix",
            currency: "Devise",
            bedrooms: "Chambres",
            bathrooms: "Salles de bain",
            surface: "Surface",
            yearOfConstruction: "Année de construction",
            garages: "Garages",
            swimmingPools: "Piscines",
            address: "Adresse complète",
            addressPlaceholder: "Numéro, rue, quartier...",
            city: "Ville",
            cityPlaceholder: "Nom de la ville",
            country: "Pays",
            countryPlaceholder: "Nom du pays",
            location: "Localisation",
            coordinates: "Coordonnées GPS",
            noLocationSelected: "Aucune localisation sélectionnée",
            geocodeAddress: "Localiser l'adresse",
            showMap: "Afficher la carte",
            hideMap: "Masquer la carte",
            mapInstructions: "Cliquez sur la carte pour positionner votre bien",
            propertyLocation: "Emplacement du bien",
            loadingMap: "Chargement de la carte...",
            locationHelperText: "Une localisation précise facilite la recherche des visiteurs.",
            mainImage: "Photo principale",
            addMainImage: "Ajouter une photo principale",
            mainImageDescription: "Cette photo sera mise en avant dans votre annonce",
            otherImages: "Photos supplémentaires",
            otherImagesDescription: "Ajoutez jusqu'à 10 photos pour valoriser votre bien",
            selectCurrency: "Sélectionner une devise",
            searchCurrency: "Rechercher une devise...",
            noCurrencyFound: "Aucune devise trouvée",
            noPopularCurrency: "Aucune devise populaire",
            pricePreview: "Aperçu du prix",
        },
        validation: {
            shortDescription: {
                required: "Le titre est obligatoire.",
                min: "Le titre doit comporter au moins 10 caractères.",
                max: "Le titre ne peut dépasser 100 caractères."
            },
            longDescription: {
                max: "La description ne peut excéder 1000 caractères."
            },
            price: {
                required: "Le prix est obligatoire.",
                positive: "Le prix doit être un nombre positif.",
                integer: "Le prix doit être un nombre entier."
            },
            currency: {
                required: "La devise est obligatoire."
            },
            type: {
                required: "Le type de bien est obligatoire."
            },
            bedrooms: {
                positive: "Le nombre de chambres doit être positif.",
                integer: "Le nombre de chambres doit être un entier."
            },
            bathrooms: {
                positive: "Le nombre de salles de bain doit être positif.",
                integer: "Le nombre de salles de bain doit être un entier."
            },
            year: {
                min: "L'année doit être supérieure à 1800.",
                max: "L'année ne peut pas être dans le futur."
            },
            address: {
                required: "L'adresse est obligatoire."
            },
            city: {
                required: "La ville est obligatoire."
            },
            country: {
                required: "Le pays est obligatoire."
            },
            firstImage: {
                required: "Une photo principale est obligatoire."
            },
            messageType: {
                required: "Le type de message est obligatoire."
            },
            subject: {
                required: "L'objet est obligatoire.",
                min: "L'objet doit contenir au moins 5 caractères.",
                max: "L'objet ne peut dépasser 100 caractères."
            },
            message: {
                required: "Le message est obligatoire.",
                min: "Le message doit contenir au moins 20 caractères.",
                max: "Le message ne peut dépasser 1000 caractères."
            },
            phoneNumber: {
                invalid: "Format de numéro de téléphone invalide."
            }
        },
        types: {
            apartment: "Appartement",
            house: "Maison",
            villa: "Villa",
            studio: "Studio",
            loft: "Loft",
            townhouse: "Maison de ville"
        },

        filters: {
            all: "Toutes",
            forSale: "À vendre",
            forRent: "À louer"
        },

        sort: {
            newest: "Plus récentes",
            oldest: "Plus anciennes",
            priceAsc: "Prix croissant",
            priceDesc: "Prix décroissant",
            title: "Titre A-Z"
        }
    },

    houseTypes: {
        apartment: "appartement",
        house: "maison",
        villa: "villa",
        studio: "studio",
        loft: "loft",
        townhouse: "maison de ville"
    },
}
