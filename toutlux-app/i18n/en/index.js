import common from './common.js';
import contact from './contact.js';
import documents from './documents.js';
import form from './form.js';
import home from './home.js';
import houseDetails from './houseDetails.js';
import imagePicker from './imagePicker.js';
import income from './income.js';
import listings from './listings.js';
import login from './login.js';
import onboarding from './onboarding.js';
import network from './network.js';
import notifications from './notifications.js';
import profile from './profile.js';
import security from './security.js';
import seller from './seller.js';
import settings from './settings.js';
import tabs from './tabs.js';
import terms from './terms.js';
import validation from './validation.js';
import verification from './verification.js';

export default {
    ...common,
    ...contact,
    ...tabs,
    ...login,
    ...profile,
    ...onboarding,
    ...documents,
    ...form,
    ...home,
    ...settings,
    ...terms,
    ...validation,
    ...security,
    ...income,
    ...houseDetails,
    ...imagePicker,
    ...verification,
    ...notifications,
    ...listings,
    ...seller,
    ...network
};