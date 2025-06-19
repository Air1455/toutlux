import React, { useState } from 'react';
import { View, StyleSheet, ScrollView } from 'react-native';
import {
  Text,
  Switch,
  useTheme,
  Card,
  TouchableRipple,
} from 'react-native-paper';
import { Ionicons } from '@expo/vector-icons';
import { useTranslation } from 'react-i18next';
import { useDispatch, useSelector } from 'react-redux';
import { toggleTheme } from "@/redux/themeReducer";
import { LinearGradient } from "expo-linear-gradient";
import { SafeScreen } from "@components/layout/SafeScreen";

const LANGUAGES = [
  { code: 'en', label: 'English', flag: 'us' },
  { code: 'fr', label: 'Français', flag: 'fr' },
];

export default function Settings() {
  const { t, i18n } = useTranslation();
  const dispatch = useDispatch();
  const { colors } = useTheme();
  const isDarkMode = useSelector((state) => state.theme.isDarkMode);
  const [selectedLanguage, setSelectedLanguage] = useState(i18n?.language || 'en');

  const handleLanguageChange = (code) => {
    setSelectedLanguage(code);
    i18n.changeLanguage(code);
  };

  const handleThemeToggle = () => {
    dispatch(toggleTheme());
  };

  const SettingsSection = ({ title, children }) => (
      <Card
          style={[
            styles.section,
            {
              backgroundColor: colors.surface,
              borderColor: colors.outline // ✅ CORRECTION: utiliser colors.outline au lieu de colors.secondary
            }
          ]}
          elevation={2}
      >
        <Card.Title
            title={title}
            titleStyle={{
              color: colors.onSurface, // ✅ CORRECTION: utiliser colors.onSurface
              fontWeight: 'bold'
            }}
        />
        {children}
      </Card>
  );

  return (
      <SafeScreen>
        <LinearGradient colors={[colors.background, colors.surface]} style={styles.mainContainer}>
          <ScrollView
              style={styles.scrollView}
              contentContainerStyle={styles.scrollContent}
              showsVerticalScrollIndicator={false}
          >
            {/* Section Paramètres généraux */}
            <SettingsSection title={t('settings.general_settings')}>
              <TouchableRipple
                  onPress={handleThemeToggle}
                  rippleColor={colors.primary + '20'} // ✅ CORRECTION: ajouter transparence
              >
                <View style={styles.settingItem}>
                  <View style={styles.settingItemContent}>
                    <Ionicons
                        name={isDarkMode ? "moon" : "sunny"}
                        size={24}
                        color={colors.primary} // ✅ CORRECTION: utiliser colors.primary
                    />
                    <Text style={[styles.settingItemText, { color: colors.onSurface }]}>
                      {t('settings.dark_mode')}
                    </Text>
                  </View>
                  <Switch
                      value={isDarkMode}
                      onValueChange={handleThemeToggle}
                      thumbColor={isDarkMode ? colors.primary : colors.outline}
                      trackColor={{ false: colors.surfaceVariant, true: colors.primary + '40' }}
                  />
                </View>
              </TouchableRipple>
            </SettingsSection>

            {/* Section Langue */}
            <SettingsSection title={t('settings.select_language')}>
              {LANGUAGES.map((lang) => (
                  <TouchableRipple
                      key={lang.code}
                      onPress={() => handleLanguageChange(lang.code)}
                      rippleColor={colors.primary + '20'} // ✅ CORRECTION: ajouter transparence
                  >
                    <View style={styles.settingItem}>
                      <View style={styles.settingItemContent}>
                        <Text
                            style={[
                              styles.settingItemText,
                              {
                                color: selectedLanguage === lang.code ? colors.primary : colors.onSurface,
                                fontWeight: selectedLanguage === lang.code ? 'bold' : 'normal'
                              }
                            ]}
                        >
                          {lang.label}
                        </Text>
                      </View>
                      {selectedLanguage === lang.code && (
                          <Ionicons
                              name="checkmark-circle"
                              size={24}
                              color={colors.primary}
                          />
                      )}
                    </View>
                  </TouchableRipple>
              ))}
            </SettingsSection>
            
            <SettingsSection title={t('settings.security')}>
              <TouchableRipple
                  onPress={() => {
                    // Navigation vers changement de mot de passe
                    console.log('Navigate to change password');
                  }}
                  rippleColor={colors.primary + '20'}
              >
                <View style={styles.settingItem}>
                  <View style={styles.settingItemContent}>
                    <Ionicons
                        name="lock-closed-outline"
                        size={24}
                        color={colors.primary}
                    />
                    <Text style={[styles.settingItemText, { color: colors.onSurface }]}>
                      {t('settings.change_password')}
                    </Text>
                  </View>
                  <Ionicons
                      name="chevron-forward"
                      size={20}
                      color={colors.onSurfaceVariant}
                  />
                </View>
              </TouchableRipple>
              <TouchableRipple
                  onPress={() => {
                    // Contacter le support
                    console.log('Contact support');
                  }}
                  rippleColor={colors.primary + '20'}
              >
                <View style={styles.settingItem}>
                  <View style={styles.settingItemContent}>
                    <Ionicons
                        name="mail-outline"
                        size={24}
                        color={colors.primary}
                    />
                    <Text style={[styles.settingItemText, { color: colors.onSurface }]}>
                      {t('settings.contact_support')}
                    </Text>
                  </View>
                  <Ionicons
                      name="chevron-forward"
                      size={20}
                      color={colors.onSurfaceVariant}
                  />
                </View>
              </TouchableRipple>
            </SettingsSection>

            {/* Version de l'app */}
            <View style={styles.versionContainer}>
              <Text style={[styles.versionText, { color: colors.onSurfaceVariant }]}>
                {t('settings.version')} 1.0.0
              </Text>
            </View>
          </ScrollView>
        </LinearGradient>
      </SafeScreen>
  );
}

// ✅ CORRECTION: StyleSheet défini APRÈS le composant
const styles = StyleSheet.create({
  mainContainer: {
    flex: 1,
  },
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    padding: 16,
    paddingBottom: 20,
    gap: 16,
  },
  section: {
    borderRadius: 16,
    padding: 5,
    borderWidth: 1,
    borderColor: 'transparent',
    overflow: 'hidden',
  },
  settingItem: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: 16,
    minHeight: 56, // ✅ AJOUT: hauteur minimum pour meilleure UX
  },
  settingItemContent: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 16,
    flex: 1, // ✅ AJOUT: prendre l'espace disponible
  },
  settingItemText: {
    fontSize: 16,
    lineHeight: 24, // ✅ AJOUT: meilleure lisibilité
  },
  versionContainer: {
    alignItems: 'center',
    paddingVertical: 16,
  },
  versionText: {
    fontSize: 14,
    fontStyle: 'italic',
  },
});