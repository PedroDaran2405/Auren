import React from 'react';
import { ActivityIndicator, KeyboardAvoidingView, Modal, Platform, Pressable, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Search, X } from './icons';
import { colors as c,font,u } from './ui';
export function SearchBox({value,onChange,placeholder='Buscar',label='Buscar'}:{value:string;onChange:(s:string)=>void;placeholder?:string;label?:string}){
 return <View style={f.search}><Search color={c.muted} size={18} accessible={false}/><TextInput value={value} onChangeText={onChange} placeholder={placeholder} placeholderTextColor={c.muted} accessibilityLabel={label} maxLength={80} style={[f.input,{flex:1,padding:0,borderWidth:0,minHeight:25}]} autoCapitalize="none" returnKeyType="search"/>{!!value&&<Pressable accessibilityRole="button" accessibilityLabel="Borrar búsqueda" onPress={()=>onChange('')} hitSlop={10}><X size={17} color={c.muted}/></Pressable>}</View>;
}
export function Sheet({title,onClose,busy=false,children}:{title:string;onClose:()=>void;busy?:boolean;children:React.ReactNode}){
 return <Modal visible animationType="slide" onRequestClose={()=>{if(!busy)onClose();}}><SafeAreaView style={{flex:1,backgroundColor:c.bg}}><KeyboardAvoidingView behavior={Platform.OS==='ios'?'padding':undefined} style={{flex:1}}><View style={[u.between,{padding:22,borderBottomWidth:1,borderBottomColor:c.line}]}><Text accessibilityRole="header" style={u.sectionTitle}>{title}</Text><Pressable accessibilityRole="button" accessibilityLabel="Cerrar ventana" disabled={busy} onPress={onClose} hitSlop={12}><X size={22} color={c.muted}/></Pressable></View><ScrollView keyboardShouldPersistTaps="handled" contentContainerStyle={{padding:22,gap:20,width:'100%',maxWidth:620,alignSelf:'center'}}>{children}</ScrollView></KeyboardAvoidingView></SafeAreaView></Modal>;
}
export function Feedback({error,notice,busy,loading}:{error?:string;notice?:string;busy?:boolean;loading?:boolean}){return <>{!!error&&<Text accessibilityRole="alert" style={f.error}>{error}</Text>}{!!notice&&<Text accessibilityLiveRegion="polite" style={f.notice}>{notice}</Text>}{(busy||loading)&&<View style={{alignItems:'center',gap:10,paddingVertical:12}}><ActivityIndicator color={c.mint} accessibilityLabel={busy?'Guardando cambios':'Cargando contenido'}/>{loading&&!busy&&<Text style={u.caption}>Cargando contenido…</Text>}</View>}</>;}
export const f=StyleSheet.create({
 input:{minHeight:52,borderWidth:1,borderColor:c.line,borderRadius:13,backgroundColor:c.surface,padding:15,color:c.text,fontFamily:font.regular,fontSize:14,lineHeight:23},
 search:{flexDirection:'row',alignItems:'center',gap:10,borderWidth:1,borderColor:c.line,borderRadius:14,padding:14,backgroundColor:c.surface},
 textArea:{minHeight:160,textAlignVertical:'top'},error:{padding:14,borderRadius:12,backgroundColor:'#351f2c',fontFamily:font.regular,color:'#ffd1df',lineHeight:23},notice:{fontFamily:font.regular,color:c.mint,lineHeight:22},
 chips:{flexDirection:'row',gap:8,flexWrap:'wrap'},chip:{paddingHorizontal:14,paddingVertical:9,borderWidth:1,borderColor:c.line,borderRadius:20},selected:{backgroundColor:c.primary,borderColor:c.primary},chipText:{fontFamily:font.medium,fontSize:12,color:c.muted},selectedText:{color:c.text},panel:{padding:18,gap:14,borderRadius:18,borderWidth:1,borderColor:c.line,backgroundColor:c.surface},empty:{padding:25,gap:10,alignItems:'center'},
});
