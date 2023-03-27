<script setup lang="ts">
import axios from "axios";
import { onBeforeMount } from "vue";
import { useError } from "../AppAxiosResp";
import { useRouter } from "vue-router";
import { useWebStore } from "../AppRouter";

const web = useWebStore();
const router = useRouter();

onBeforeMount(() => {
    axios
        .post(import.meta.env.VITE_API_ENDPOINT + "/api/post-token-revoke")
        .then(() => {
            router.push(web.landingPage);
        })
        .catch((error) => {
            useError(error);
        });
});
</script>
