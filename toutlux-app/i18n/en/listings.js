export default {
    listings: {
        title: "My listings",
        myListings: "My listings",
        createFirst: "Create your first listing",
        createFirstListing: "Publish your first listing to start receiving quality contacts",
        manageAll: "Manage all my listings",
        moreListings: "{{count}} more listings",
        viewAll: "View all",
        noResultsFound: "No results found",
        noListingsYet: "No listings available at the moment",
        tryDifferentFilters: "Try different filters to refine your search",
        create: "Create listing",
        edit: "Edit",
        delete: "Delete",
        deleteTitle: "Delete listing",
        deleteConfirmation: "Are you sure you want to delete the listing \"{{title}}\"? This action cannot be undone.",
        deleteSuccess: "Listing deleted successfully.",
        deleteError: "An error occurred while deleting the listing.",
        deleteMultipleTitle: "Delete selected listings",
        deleteMultipleConfirmation: "Are you sure you want to delete these {{count}} listings? This action cannot be undone.",
        deleteMultipleSuccess: "{{count}} listings deleted successfully.",
        createSuccess: "Listing created successfully.",
        createError: "An error occurred while creating the listing.",
        updateSuccess: "Listing updated successfully.",
        updateError: "An error occurred while updating the listing.",
        loadingListing: "Loading listing...",
        listingNotFound: "Listing not found.",
        initializingForm: "Initializing form...",
        editListing: "Edit listing",
        saveChanges: "Save changes",
        unsavedChangesTitle: "Unsaved changes",
        unsavedChangesMessage: "You have unsaved changes. Do you want to discard them?",
        forRent: "For rent",
        forSale: "For sale",
        maxImagesReached: "Maximum of {{max}} images reached.",
        stepProgress: "Step {{current}} of {{total}}",
        totalListings: "{{count}} listing(s) total",
        filteredResults: "{{count}} result(s)",
        selectedCount: "{{count}}/{{total}} selected",
        searchPlaceholder: "Search in my listings...",

        steps: {
            basicInfo: "Basic information",
            details: "Details and price",
            location: "Location",
            images: "Photos"
        },

        form: {
            shortDescription: "Listing title",
            shortDescriptionPlaceholder: "Ex: Beautiful 3-room apartment with sea view",
            longDescription: "Detailed description",
            longDescriptionPlaceholder: "Describe your property in detail: features, amenities, surroundings...",
            propertyType: "Property type",
            listingType: "Listing type",
            price: "Price",
            currency: "Currency",
            bedrooms: "Bedrooms",
            bathrooms: "Bathrooms",
            surface: "Surface area",
            yearOfConstruction: "Year built",
            garages: "Garages",
            swimmingPools: "Swimming pools",
            address: "Full address",
            addressPlaceholder: "Number, street, neighborhood...",
            city: "City",
            cityPlaceholder: "City name",
            country: "Country",
            countryPlaceholder: "Country name",
            location: "Location",
            coordinates: "GPS coordinates",
            noLocationSelected: "No location selected",
            geocodeAddress: "Locate address",
            showMap: "Show map",
            hideMap: "Hide map",
            mapInstructions: "Click on the map to position your property",
            propertyLocation: "Property location",
            loadingMap: "Loading map...",
            locationHelperText: "Precise location helps visitors better find your property.",
            mainImage: "Main photo",
            addMainImage: "Add main photo",
            mainImageDescription: "This photo will be highlighted in your listing",
            otherImages: "Additional photos",
            otherImagesDescription: "Add up to 10 photos to showcase your property",
            selectCurrency: "Select a currency",
            searchCurrency: "Search for a currency...",
            noCurrencyFound: "No currency found",
            noPopularCurrency: "No popular currency",
            pricePreview: "Price preview",
            validation: {
                shortDescription: {
                    required: "Title is required.",
                    min: "Title must be at least 10 characters.",
                    max: "Title cannot exceed 100 characters."
                },
                longDescription: {
                    max: "Description cannot exceed 1000 characters."
                },
                price: {
                    required: "Price is required.",
                    positive: "Price must be a positive number.",
                    integer: "Price must be an integer."
                },
                currency: {
                    required: "Currency is required."
                },
                type: {
                    required: "Property type is required."
                },
                bedrooms: {
                    positive: "Number of bedrooms must be positive.",
                    integer: "Number of bedrooms must be an integer."
                },
                bathrooms: {
                    positive: "Number of bathrooms must be positive.",
                    integer: "Number of bathrooms must be an integer."
                },
                year: {
                    min: "Year must be greater than 1800.",
                    max: "Year cannot be in the future."
                },
                address: {
                    required: "Address is required."
                },
                city: {
                    required: "City is required."
                },
                country: {
                    required: "Country is required."
                },
                firstImage: {
                    required: "A main photo is required."
                },
                messageType: {
                    required: "Message type is required."
                },
                subject: {
                    required: "Subject is required.",
                    min: "Subject must be at least 5 characters.",
                    max: "Subject cannot exceed 100 characters."
                },
                message: {
                    required: "Message is required.",
                    min: "Message must be at least 20 characters.",
                    max: "Message cannot exceed 1000 characters."
                },
                phoneNumber: {
                    invalid: "Invalid phone number format."
                }
            }
        },

        types: {
            apartment: "Apartment",
            house: "House",
            villa: "Villa",
            studio: "Studio",
            loft: "Loft",
            townhouse: "Townhouse"
        },

        filters: {
            all: "All",
            forSale: "For sale",
            forRent: "For rent"
        },

        sort: {
            newest: "Newest",
            oldest: "Oldest",
            priceAsc: "Price ascending",
            priceDesc: "Price descending",
            title: "Title A-Z"
        }
    },

    houseTypes: {
        apartment: "apartment",
        house: "house",
        villa: "villa",
        studio: "studio",
        loft: "loft",
        townhouse: "townhouse"
    },
}
