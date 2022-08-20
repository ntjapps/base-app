<script>
  import CmpAppSet from './CmpAppSet.vue'
  import PgServerLog from './PgServerLog.vue'

  export default {
    components: {
      CmpAppSet,
      PgServerLog,
    },

    setup() {
      /**
       * Get new CSRF Token set everytime app is created
       */
      axios.get('/sanctum/csrf-cookie').then(() => {
        console.log('csrf cookie init')
      })
    },
  }
</script>