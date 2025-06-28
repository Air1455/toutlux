import React, { useState } from 'react';
import { View, StyleSheet, ScrollView } from 'react-native';
import {
  Switch,
  useTheme,
  Card,
  TouchableRipple,
} from 'react-native-paper';
import { Ionicons } from '@expo/vector-icons';
import { useTranslation } from 'react-i18next';
import { useDispatch, useSelector } from 'react-redux';
import { useRouter } from 'expo-router';
import { toggleTheme } from "@/redux/themeReducer";
import { LinearGradient } from "expo-linear-gradient";
import { SafeScreen } from "@components/layout/SafeScreen";
import Text from '@/components/typography/Text';
import { SPACING, BORDER_RADIUS, ELEVATION } from '@/constants/spacing';

const LANGUAGES = [
  { code: 'en', label: 'English', flag: 'us' },
  { code: 'fr', label: 'Français', flag: 'fr' },
];

export default function Settings() {
  const { t, i18n } = useTranslation();
  const dispatch = useDispatch();
  const router = useRouter();
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
              borderColor: colors.outline
            }
          ]}
          elevation={ELEVATION.medium}
      >
        <Card.Title
            title={title}
            titleStyle={{ color: colors.onSurface }}
            titleVariant="titleMedium"
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
            {/* En-tête */}
            <View style={styles.header}>
              <Text variant="pageTitle" color="textPrimary">
                {t('settings.title')}
              </Text>
            </View>

            {/* Section Paramètres généraux */}
            <SettingsSection title={t('settings.generalSettings')}>
              <TouchableRipple
                  onPress={handleThemeToggle}
                  rippleColor={colors.primary + '20'}
              >
                <View style={styles.settingItem}>
                  <View style={styles.settingItemContent}>
                    <Ionicons
                        name={isDarkMode ? "moon" : "sunny"}
                        size={24}
                        color={colors.primary}
                    />
                    <Text variant="bodyLarge" color="textPrimary">
                      {t('settings.darkMode')}
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
            <SettingsSection title={t('settings.selectLanguage')}>
              {LANGUAGES.map((lang) => (
                  <TouchableRipple
                      key={lang.code}
                      onPress={() => handleLanguageChange(lang.code)}
                      rippleColor={colors.primary + '20'}
                  >
                    <View style={styles.settingItem}>
                      <View style={styles.settingItemContent}>
                        <Text
                            variant="bodyLarge"
                            color={selectedLanguage === lang.code ? "primary" : "textPrimary"}
                            style={{
                              fontWeight: selectedLanguage === lang.code ? 'bold' : 'normal'
                            }}
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

            {/* Section Sécurité */}
            <SettingsSection title={t('settings.security')}>
              <TouchableRipple
                  onPress={() => {
                    router.push('../screens/change_password');
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
                    <Text variant="bodyLarge" color="textPrimary">
                      {t('settings.changePassword')}
                    </Text>
                  </View>
                  <Ionicons
                      name="chevron-forward"
                      size={20}
                      color={colors.textSecondary}
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
                    <Text variant="bodyLarge" color="textPrimary">
                      {t('settings.contactSupport')}
                    </Text>
                  </View>
                  <Ionicons
                      name="chevron-forward"
                      size={20}
                      color={colors.textSecondary}
                  />
                </View>
              </TouchableRipple>
            </SettingsSection>

            {/* Section À propos */}
            <SettingsSection title={t('settings.about')}>
              <View style={styles.settingItem}>
                <View style={styles.settingItemContent}>
                  <Ionicons
                      name="information-circle-outline"
                      size={24}
                      color={colors.primary}
                  />
                  <View style={styles.aboutContent}>
                    <Text variant="bodyLarge" color="textPrimary">
                      {t('settings.appName')}
                    </Text>
                    <Text variant="labelMedium" color="textSecondary">
                      {t('settings.version')} 1.0.0
                    </Text>
                  </View>
                </View>
              </View>
            </SettingsSection>

            {/* Version de l'app */}
            <View style={styles.versionContainer}>
              <Text variant="labelMedium" color="textSecondary">
                © 2025 • {t('settings.madeWithLove')}
              </Text>
            </View>
          </ScrollView>
        </LinearGradient>
      </SafeScreen>
  );
}

const styles = StyleSheet.create({
  mainContainer: {
    flex: 1,
  },
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    padding: SPACING.lg,
    paddingBottom: SPACING.xl,
    gap: SPACING.lg,
  },
  header: {
    paddingHorizontal: SPACING.xs,
    paddingVertical: SPACING.sm,
    marginBottom: SPACING.sm,
  },
  section: {
    borderRadius: BORDER_RADIUS.lg,
    padding: SPACING.xs,
    borderWidth: 1,
    borderColor: 'transparent',
    overflow: 'hidden',
  },
  sectionTitleStyle: {
    color: undefined, // Laisse Paper gérer la couleur
  },
  settingItem: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: SPACING.lg,
    minHeight: 56,
  },
  settingItemContent: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: SPACING.lg,
    flex: 1,
  },
  aboutContent: {
    flex: 1,
    gap: SPACING.xs,
  },
  versionContainer: {
    alignItems: 'center',
    paddingVertical: SPACING.lg,
    marginTop: SPACING.sm,
  },
});