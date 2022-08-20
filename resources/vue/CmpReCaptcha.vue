<script>
  export default {
    props: {
      captchaSiteKey: String,
    },
    
    data() {
      return {
        widgetId: null,
      }
    },

    methods: {
      execute () {
        this.init()
      },
      reset () {
        window.grecaptcha.reset(this.widgetId)
      },
      init () {
        if (window.grecaptcha && this.widgetId == null) {
          console.log('grecaptcha init')
          return this.widgetId = window.grecaptcha.render('recaptcha', {
            sitekey: this.captchaSiteKey,
            badge: 'inline',
            size: 'invisible',
            callback: (response) => {
              this.$emit('verify', response)
              this.reset()
            }
          })
        } else {
          console.log('grecaptcha exec')
          window.grecaptcha.execute(this.widgetId)
        }
      },
    },

    watch: {
      widgetId(newVal) {
        this.init()
      }
    }
  }
</script>

<template>
  <div id="recaptcha"></div>
</template>