<script>
  import { useMainStore } from './AppState'
  import { useResponse, useError } from './AppAxiosResp'
  import Button from 'primevue/button'
  import Menu from 'primevue/menu'

  export default {
    components: {
      Button,
      Menu,
    },
    
    data() {
      let main = useMainStore()
      return {
        echoEnable: false,

        standardMenuItems: [
        ],

        rootMenuItems: [
          /** Super User Responsibility */
          {
            label: 'Server Systems',
            items: [
              {
                label: 'Server Log',
                icon: 'pi pi-server',
                command: () => {
                  window.location = main.serverLogs
                }
              },
            ],
          },
        ],
      }
    },

    methods: {
      logoutSubmit() {
        let main = useMainStore()
        axios.post(main.logout).then(response => {
          //console.log(response)
          useResponse(response)
        }).catch(error => {
          //console.error(error.response.data)
          useError(error)
        })
      },

      toggleMenu(event) {
        this.$refs.menu.toggle(event);
      }
    },

    computed: {
      showMenu() {
        let main = useMainStore()        
        let menu = this.standardMenuItems
        
        if (main.permissionsData?.includes('root')) {
          menu = menu.concat(this.rootMenuItems)
        }
        return menu
      }
    },

    setup() {
      const main = useMainStore()
      return { main }
    },

    mounted() {
      /**
       * Echo status change
       *//*
      window.Echo.connector.pusher.connection.bind("state_change", function (states) {
        // states = {previous: 'oldState', current: 'newState'}
        let EchoStatusElement = document.getElementById("EchoStatus");
        if (EchoStatusElement !== null) {
          switch(states.current) {
            case 'connecting':
              EchoStatusElement.className = "pi pi-spin pi-spinner echo-connect-loading";
              break;
            case 'connected':
              EchoStatusElement.className = "pi pi-circle-fill echo-connect-connected";
              break;
            case 'unavailable':
              EchoStatusElement.className = "pi pi-circle-fill echo-connect-failed";
              break;
            case 'failed':
              EchoStatusElement.className = "pi pi-circle-fill echo-connect-failed";
              break;
            case 'disconnected':
              EchoStatusElement.className = "pi pi-spin pi-spinner echo-connect-loading";
              break;
            default:
              EchoStatusElement.className = "pi pi-spin pi-spinner echo-connect-loading";
          }
        }
      })*/
    }
  }
</script>

<template>
  <div class="sticky top-0 w-full z-50 drop-shadow-xl">
    <div class="bg-indigo-800 py-3 px-5 flex flex-row">
      <div class="flex flex-row w-full">
        <Button type="button" @click="toggleMenu" icon="pi pi-bars" label="Menu" class="p-button-sm" />
        <Menu ref="menu" :model="showMenu" :popup="true" />
      </div>

      <div class="flex flex-row-reverse w-full">
        <div class="flex flex-row-reverse w-full mt-1 mb-1" v-if="echoEnable && main.browserSuppport">
          <button id="EchoStatus" class="pi pi-spin pi-spinner echo-connect-loading"></button>
        </div>
        <div class="flex flex-row-reverse w-full mt-1 mb-1" v-if="!main.browserSuppport">
          <Button class="p-button-sm p-button-danger" label="Outdated Browser" />
        </div>
      </div>
    </div>
  </div>
</template>