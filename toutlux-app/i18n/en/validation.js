export default {
    validation: {
        firstName: {
            required: "First name is required"
        },
        lastName: {
            required: "Last name is required"
        },
        email: {
            invalid: "Invalid email format",
            required: "Email is required",
            verified: "Email verified",
            pending: "Email verification pending",
            missing: "Email address missing",
            use_google_button: "Please use the Google button to sign in with a Gmail address."
        },
        password: {
            required: "Password is required"
        },
        phoneNumber: {
            invalid: "Invalid phone number format. Please enter the local number without the country code.",
            required: "Phone number is required",
            verified: "Phone verified",
            pending: "Phone verification pending",
            missing: "Phone number missing"
        },
        phoneNumberIndicatif: {
            invalid: "Invalid country code. It must contain between 1 and 4 digits.",
            required: "Country code is required"
        },
        profilePicture: {
            required: "Profile picture is required"
        },
        identityCardType: {
            required: "Identity document type is required"
        },
        identityCard: {
            required: "Identity document is required",
            verified: "Identity verified",
            pending: "Identity verification pending",
            missing: "Identity documents missing"
        },
        selfieWithId: {
            required: "Selfie with identity document is required"
        },
        termsAccepted: {
            required: "You must accept the terms of use",
            verified: "Terms of use accepted",
            pending: "Terms of use acceptance pending",
            missing: "Terms of use not accepted"
        },
        privacyAccepted: {
            required: "You must accept the privacy policy",
            verified: "Privacy policy accepted",
            pending: "Privacy policy acceptance pending",
            missing: "Privacy policy not accepted"
        },
        financial: {
            verified: "Financial documents verified",
            pending: "Financial documents pending",
            missing: "Financial documents missing"
        },
        terms: {
            accepted: "Terms accepted",
            pending: "Terms pending",
            missing: "Terms not accepted"
        },
        currentPassword: {
            required: "Current password is required"
        },
        confirmPassword: {
            mustMatch: "Passwords do not match",
            required: "Password confirmation is required"
        },
        verified: "Verified",
        pending: "Pending verification"
    }
};
