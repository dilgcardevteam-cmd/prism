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
  composeRecipients,
  handleRemoveComposeRecipient,
  composeRecipientOptions,
  handleAddComposeRecipient,
  composeMessage,
  setComposeMessage,
  composeImage,
  setComposeImage,
  pickImage,
  renderImagePreview,
  handleSendComposeMessage,
  isSending,
}) {
  return (
    <Modal visible={visible} transparent animationType="slide" onRequestClose={onClose}>
      <KeyboardAvoidingView behavior={Platform.OS === "ios" ? "padding" : "height"} style={{ flex: 1 }}>
        <View style={{ flex: 1, backgroundColor: 'rgba(0,0,0,0.45)' }}>
          <View style={{ backgroundColor: 'white', borderTopLeftRadius: 24, borderTopRightRadius: 24, padding: 16, paddingBottom: 24 }}>
            <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 }}>
              <View>
                <Text style={[{ color: APP_COLORS.primaryBlue, fontSize: 16 }, FONT_STYLES.bold]}>Start a conversation</Text>
                <Text style={[{ color: APP_COLORS.textSubtle, fontSize: 12, marginTop: 4 }, FONT_STYLES.regular]}>Select one or more recipients and send a message.</Text>
              </View>

              <Pressable onPress={onClose} style={{ height: 36, width: 36, alignItems: 'center', justifyContent: 'center', borderRadius: 18, backgroundColor: '#fff' }}>
                <Feather name="x" size={18} color={APP_COLORS.primaryBlue} />
              </Pressable>
            </View>

            <ScrollView showsVerticalScrollIndicator={false}>
              <View style={{ marginBottom: 12 }}>
                <View style={{ borderRadius: 22, borderWidth: 1, borderColor: '#dbe5f1', backgroundColor: '#fff', padding: 12 }}>
                  <Text style={[{ color: APP_COLORS.tabInactive, fontSize: 11, textTransform: 'uppercase', letterSpacing: 1 }, FONT_STYLES.semiBold]}>Recipients</Text>
                  <View style={{ marginTop: 8, flexDirection: 'row', flexWrap: 'wrap', gap: 8 }}>
                    {composeRecipients.length ? composeRecipients.map((recipient) => (
                      <View key={recipient.id} style={{ flexDirection: 'row', alignItems: 'center', gap: 8, borderRadius: 999, paddingHorizontal: 12, paddingVertical: 8, backgroundColor: APP_COLORS.primaryBlueLight }}>
                        <View style={{ height: 24, width: 24, alignItems: 'center', justifyContent: 'center', borderRadius: 12, backgroundColor: APP_COLORS.primaryBlue }}>
                          <Text style={{ color: '#fff', fontSize: 9, fontWeight: '800' }}>{recipient.initials}</Text>
                        </View>
                        <Text style={[{ maxWidth: 140, fontSize: 12, color: APP_COLORS.primaryBlue }, FONT_STYLES.semiBold]} numberOfLines={1}>{recipient.name}</Text>
                        <Pressable onPress={() => handleRemoveComposeRecipient(recipient.id)}>
                          <Feather name="x" size={14} color={APP_COLORS.primaryBlue} />
                        </Pressable>
                      </View>
                    )) : (
                      <Text style={[{ color: APP_COLORS.textSubtle, fontSize: 14 }, FONT_STYLES.regular]}>Add recipients to begin.</Text>
                    )}
                  </View>

                  <View style={{ marginTop: 12, flexDirection: 'row', alignItems: 'center', gap: 8, borderRadius: 999, borderWidth: 1, borderColor: '#dbe5f1', backgroundColor: '#f8fbff', paddingHorizontal: 12, paddingVertical: 8 }}>
                    <Feather name="search" size={16} color={APP_COLORS.tabInactive} />
                    <TextInput
                      value={composeQuery}
                      onChangeText={setComposeQuery}
                      placeholder="Search recipients"
                      placeholderTextColor={APP_COLORS.tabInactive}
                      style={[{ flex: 1, fontSize: 14, color: APP_COLORS.primaryBlue }, FONT_STYLES.regular]}
                    />
                  </View>
                </View>

                <View style={{ marginTop: 12, maxHeight: 200, borderRadius: 22, borderWidth: 1, borderColor: '#dbe5f1', backgroundColor: '#fff', overflow: 'hidden' }}>
                  <ScrollView nestedScrollEnabled showsVerticalScrollIndicator={false}>
                    {composeRecipientOptions.length ? composeRecipientOptions.map((user) => (
                      <Pressable key={user.id} onPress={() => handleAddComposeRecipient(user)} style={{ flexDirection: 'row', alignItems: 'center', gap: 12, borderBottomWidth: 1, borderBottomColor: '#eef3f8', paddingHorizontal: 12, paddingVertical: 10 }}>
                        <View style={{ height: 40, width: 40, alignItems: 'center', justifyContent: 'center', borderRadius: 12, backgroundColor: `${APP_COLORS.primaryBlue}12` }}>
                          <Text style={{ color: APP_COLORS.primaryBlue, fontSize: 11, fontWeight: '800' }}>{(user.name || 'U').split(' ').map(p=>p[0]).slice(0,2).join('').toUpperCase()}</Text>
                        </View>
                        <View style={{ flex: 1 }}>
                          <Text style={[{ fontSize: 14, color: APP_COLORS.primaryBlue }, FONT_STYLES.semiBold]} numberOfLines={1}>{user?.name || 'Unknown User'}</Text>
                          <Text style={[{ marginTop: 2, fontSize: 12, color: APP_COLORS.textSubtle }, FONT_STYLES.regular]} numberOfLines={1}>{[user?.position, user?.office].filter(Boolean).join(' • ') || 'PDMU User'}</Text>
                        </View>
                        <Feather name="plus-circle" size={18} color={APP_COLORS.primaryBlue} />
                      </Pressable>
                    )) : (
                      <View style={{ padding: 16 }}>
                        <Text style={[{ fontSize: 15, color: '#1e3a8a' }, FONT_STYLES.semiBold]}>No recipients available</Text>
                        <Text style={[{ marginTop: 6, fontSize: 12, color: '#64748b' }, FONT_STYLES.regular]}>There are no selectable users right now.</Text>
                      </View>
                    )}
                  </ScrollView>
                </View>
              </View>

              <View style={{ marginTop: 12, borderRadius: 22, borderWidth: 1, borderColor: '#dbe5f1', backgroundColor: '#fff', padding: 12 }}>
                <Text style={[{ color: APP_COLORS.tabInactive, fontSize: 11, textTransform: 'uppercase', letterSpacing: 1 }, FONT_STYLES.semiBold]}>Message</Text>
                <TextInput
                  value={composeMessage}
                  onChangeText={setComposeMessage}
                  placeholder="Write something thoughtful..."
                  placeholderTextColor={APP_COLORS.tabInactive}
                  multiline
                  style={[{ marginTop: 8, minHeight: 96, borderRadius: 18, borderWidth: 1, borderColor: '#dbe5f1', backgroundColor: '#f8fbff', paddingHorizontal: 12, paddingVertical: 10, fontSize: 14, color: APP_COLORS.primaryBlue, textAlignVertical: 'top' }, FONT_STYLES.regular]}
                />

                {renderImagePreview && renderImagePreview(composeImage, () => setComposeImage(null))}

                <View style={{ marginTop: 12, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 12 }}>
                  <Pressable onPress={async () => { const image = await pickImage(); if (image) { setComposeImage(image); } }} style={{ flexDirection: 'row', alignItems: 'center', gap: 8, borderRadius: 999, paddingHorizontal: 12, paddingVertical: 10, backgroundColor: APP_COLORS.primaryBlueLight }}>
                    <Feather name="image" size={16} color={APP_COLORS.primaryBlue} />
                    <Text style={[{ fontSize: 14, color: APP_COLORS.primaryBlue }, FONT_STYLES.semiBold]}>Add image</Text>
                  </Pressable>

                  <Pressable onPress={handleSendComposeMessage} disabled={isSending} style={{ flexDirection: 'row', alignItems: 'center', gap: 8, borderRadius: 999, paddingHorizontal: 16, paddingVertical: 10, backgroundColor: APP_COLORS.primaryBlue, opacity: isSending ? 0.75 : 1 }}>
                    {isSending ? <ActivityIndicator size="small" color="#fff" /> : <Feather name="send" size={16} color="#fff" />}
                    <Text style={[{ fontSize: 14, color: '#fff' }, FONT_STYLES.bold]}>Send</Text>
                  </Pressable>
                </View>
              </View>
            </ScrollView>
          </View>
        </View>
      </KeyboardAvoidingView>
    </Modal>
  );
}
