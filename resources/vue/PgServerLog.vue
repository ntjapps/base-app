<script>
  import { useMainStore } from './AppState'
  import CmpHeader from './CmpHeader.vue'
  import CmpFooter from './CmpFooter.vue'
  import Button from 'primevue/button'
  import DataTable from 'primevue/datatable'
  import Column from 'primevue/column'
  import ColumnGroup from 'primevue/columngroup'
  import Row from 'primevue/row'

  export default {
    props: {
    },

    components: {
      CmpHeader,
      CmpFooter,
      Button,
      DataTable,
      Column,
      ColumnGroup,
      Row,
    },
    
    data() {
      return {
        serverLogData: null,
      }
    },

    methods: {
      getServerLogData() {
        Swal.fire({
          title: 'Loading data . . .',
          allowOutsideClick: false,
          showConfirmButton: false,
          didOpen: () => {
            Swal.showLoading()
            axios.post(useMainStore().getServerLogs).then(response => {
              //console.log(response.data)
              this.serverLogData = response.data
              Swal.close()
            }).catch(error => {
              console.error(error.response.data)
            })
          }
        })
      }
    },

    setup() {
      const main = useMainStore()
      return { main }
    },

    created() {
      /** Check if store is already patched */
      const main = useMainStore()
      main.$subscribe((mutation) => {
        if(typeof mutation.payload.getServerLogs != 'undefined') {
          this.getServerLogData()
        }
      })
    },
  }
</script>

<template>
  <div class="min-h-screen h-auto bg-slate-200">
    <CmpHeader />
    <div class="grid min-h-fit content-start">
      <div class="my-3 mx-5 p-5 bg-white rounded-lg drop-shadow-lg">
        <div class="flex justify-between">
          <h2 class="title-font font-bold my-auto">Server Log</h2>
          <div>
            <Button class="p-button-sm" icon="pi pi-refresh" @click.prevent="getServerLogData" />
          </div>
        </div>
      </div>
      <div class="my-3 mx-5 p-1 bg-white rounded-lg drop-shadow-lg overflow-auto">
        <DataTable class="p-datatable-sm text-sm" :value="serverLogData" :dataKey="'id'" :resizableColumns="true" columnResizeMode="expand" showGridlines responsiveLayout="scroll" :paginator="true" :rows="20" :sortField="'id'" :sortOrder="-1" >
          <Column field="id" header="Log ID" />
          <Column field="unix_time" header="Log Date">
            <template #body="slotProps">
              {{ new Date(slotProps.data.unix_time * 1000).toLocaleString() }}
            </template>
          </Column>
          <Column field="message" header="Log Message" />
          <Column field="context" header="Log Extra" />
          <Column field="level" header="Level ID" />
          <Column field="level_name" header="Level Name" />
        </DataTable>
      </div>
    </div>
    <CmpFooter />
  </div>
</template>