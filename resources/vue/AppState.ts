import { defineStore } from 'pinia'
export const useMainStore = defineStore('main', {
  state: () => {
    return {
      /** WEB requests */
      landingPage: String,
      otpForm: String,
      profile: String,
      dashboard: String,
      serverLogs: String,
      
      /** API request */
      logout: String,
      requestOtp: String,
      submitOtp: String,
      getAllUserPermission: String,
      updateProfile: String,
      getServerLogs: String,

      /** Additional data */
      browserSuppport: null,
      permissionsData: [],
    }
  }
})