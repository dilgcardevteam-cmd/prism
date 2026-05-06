import { Feather } from "@expo/vector-icons";
import { KeyboardAvoidingView, Modal, Platform, Pressable, ScrollView, Text, TextInput, View, ActivityIndicator } from "react-native";
import { APP_COLORS } from "../../../../constants/theme";
import { useMemo } from "react";

const FONT_STYLES = {
  regular: { fontFamily: "Montserrat-Regular" },
  semiBold: { fontFamily: "Montserrat-SemiBold" },
  bold: { fontFamily: "Montserrat-Bold" },
};

export default function ComposeBottomSheet({
  visible,
  onClose,
  composeQuery,
  setComposeQuery,
  composeRecipientOptions,
  onPickRecipient,
}) {
  return (
    <Modal visible={visible} transparent animationType="slide" onRequestClose={onClose}>
      <View style={{ flex: 1, backgroundColor: "rgba(0,0,0,0.45)" }}>
        <View style={{ flex: 1, justifyContent: "flex-end" }}>
          <View style={{ maxHeight: "88%", backgroundColor: "white", borderTopLeftRadius: 24, borderTopRightRadius: 24, padding: 16, paddingBottom: 24 }}>
            <View style={{ flexDirection: "row", justifyContent: "space-between", alignItems: "center", marginBottom: 8 }}>
              <View>
                <Text style={[{ color: APP_COLORS.primaryBlue, fontSize: 16 }, FONT_STYLES.bold]}>Start a conversation</Text>
                <Text style={[{ color: APP_COLORS.textSubtle, fontSize: 12, marginTop: 4 }, FONT_STYLES.regular]}>Tap a recipient to open the conversation page.</Text>
              </View>

              <Pressable onPress={onClose} style={{ height: 36, width: 36, alignItems: "center", justifyContent: "center", borderRadius: 18, backgroundColor: "#fff" }}>
                <Feather name="x" size={18} color={APP_COLORS.primaryBlue} />
              </Pressable>
            </View>

            <View style={{ flexDirection: "row", alignItems: "center", gap: 8, borderRadius: 999, borderWidth: 1, borderColor: "#dbe5f1", backgroundColor: "#f8fbff", paddingHorizontal: 12, paddingVertical: 8 }}>
              <Feather name="search" size={16} color={APP_COLORS.tabInactive} />
              <TextInput
                value={composeQuery}
                onChangeText={setComposeQuery}
                placeholder="Search recipients"
                placeholderTextColor={APP_COLORS.tabInactive}
                style={[{ flex: 1, fontSize: 14, color: APP_COLORS.primaryBlue }, FONT_STYLES.regular]}
              />
            </View>

            <ScrollView showsVerticalScrollIndicator={false} style={{ marginTop: 12 }}>
              <View style={{ borderRadius: 22, borderWidth: 1, borderColor: '#dbe5f1', backgroundColor: '#fff', overflow: 'hidden' }}>
                {composeRecipientOptions.length ? composeRecipientOptions.map((user) => (
                  <Pressable
                    key={user.id}
                    onPress={() => onPickRecipient(user)}
                    style={({ pressed }) => ({
                      flexDirection: 'row',
                      alignItems: 'center',
                      gap: 12,
                      borderBottomWidth: 1,
                      borderBottomColor: '#eef3f8',
                      paddingHorizontal: 12,
                      paddingVertical: 12,
                      opacity: pressed ? 0.84 : 1,
                    })}
                  >
                    <View style={{ height: 42, width: 42, alignItems: 'center', justifyContent: 'center', borderRadius: 12, backgroundColor: `${APP_COLORS.primaryBlue}12` }}>
                      <Text style={{ color: APP_COLORS.primaryBlue, fontSize: 11, fontWeight: '800' }}>{(user.name || 'U').split(' ').map((part) => part[0]).slice(0, 2).join('').toUpperCase()}</Text>
                    </View>
                    <View style={{ flex: 1 }}>
                      <Text style={[{ fontSize: 14, color: APP_COLORS.primaryBlue }, FONT_STYLES.semiBold]} numberOfLines={1}>{user?.name || 'Unknown User'}</Text>
                      <Text style={[{ marginTop: 2, fontSize: 12, color: APP_COLORS.textSubtle }, FONT_STYLES.regular]} numberOfLines={1}>{[user?.position, user?.office].filter(Boolean).join(' • ') || 'PDMU User'}</Text>
                    </View>
                    <Feather name="chevron-right" size={18} color={APP_COLORS.tabInactive} />
                  </Pressable>
                )) : (
                  <View style={{ padding: 16 }}>
                    <Text style={[{ fontSize: 15, color: '#1e3a8a' }, FONT_STYLES.semiBold]}>No recipients available</Text>
                    <Text style={[{ marginTop: 6, fontSize: 12, color: '#64748b' }, FONT_STYLES.regular]}>There are no selectable users right now.</Text>
                  </View>
                )}
              </View>
            </ScrollView>
          </View>
        </View>
      </View>
    </Modal>
  );
}
