<script>
  import { useMainStore } from './AppState'
  
  export default {
    methods: {
      init(main) {
        /** Get Constant */
        axios.post('/api/app-const').then(response => {
          //console.log(response)
          /** WEB Request */
          main.$patch({landingPage: response.data.landingPage})
          main.$patch({otpForm: response.data.otpForm})
          main.$patch({profile: response.data.profile})
          main.$patch({dashboard: response.data.dashboard})
          main.$patch({serverLogs: response.data.serverLogs})

          /** API Request */
          main.$patch({logout: response.data.logout})
          main.$patch({requestOtp: response.data.requestOtp})
          main.$patch({submitOtp: response.data.submitOtp})
          main.$patch({getAllUserPermission: response.data.getAllUserPermission})
          main.$patch({updateProfile: response.data.updateProfile})
          main.$patch({getServerLogs: response.data.getServerLogs})

          /** Send response data to after init function & if user authenticated */
          if (response.data.isAuth) {
            this.authInit(response.data)
          }
        }).catch(error => {
          console.error(error.response.data)
        })
      },

      authInit(data) {
        /** Get Template
        axios.post(data.getTemplate).then(response => {
          //console.log(response)
          useMainStore().$patch({})
        }).catch(error => {
          console.error(error.response.data)
        }) */
      }
    },

    setup() {
      const main = useMainStore()
      return { main }
    },

    created() {
      const main = useMainStore()
      /**
       * Test if browser is compatible
       */
      if (window.supportedBrowsers.test(navigator.userAgent)) {
        useMainStore().$patch({browserSuppport: true})
      } else {
        useMainStore().$patch({browserSuppport: false})
      }

      /** Init all constant */
      this.init(main)
    },
  }
</script>

<template>
</template>