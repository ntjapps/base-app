<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { timeGreetings } from '../AppCommon';
import { useMainStore } from '../AppState';

import CmpLayout from '../Components/CmpLayout.vue';

const timeGreet = timeGreetings();
const main = useMainStore();
const { userName, appName } = storeToRefs(main);
</script>

<template>
    <CmpLayout>
        <div class="mx-auto w-full max-w-6xl space-y-5">
            <div
                class="rounded-xl border border-gray-200 bg-gradient-to-r from-white to-gray-50 p-5 shadow-sm"
            >
                <h2 class="text-4xl font-semibold tracking-tight text-gray-800">
                    {{ timeGreet + userName }}
                </h2>
                <h3 class="mt-1 text-sm text-gray-600">Welcome to {{ appName }}</h3>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="text-sm font-bold text-gray-800">System Status</div>
                    <div class="mb-3 text-xs text-gray-500">Real-time performance chart</div>

                    <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-3">
                        <div class="mb-3 flex h-20 items-end gap-1">
                            <div class="h-4 w-2 rounded-sm bg-green-200" />
                            <div class="h-6 w-2 rounded-sm bg-green-300" />
                            <div class="h-5 w-2 rounded-sm bg-green-200" />
                            <div class="h-8 w-2 rounded-sm bg-green-400" />
                            <div class="h-7 w-2 rounded-sm bg-green-300" />
                            <div class="h-10 w-2 rounded-sm bg-green-500" />
                            <div class="h-8 w-2 rounded-sm bg-green-300" />
                            <div class="h-12 w-2 rounded-sm bg-green-500" />
                            <div class="h-9 w-2 rounded-sm bg-green-400" />
                            <div class="h-14 w-2 rounded-sm bg-green-600" />
                            <div class="h-11 w-2 rounded-sm bg-green-400" />
                            <div class="h-16 w-2 rounded-sm bg-green-600" />
                        </div>

                        <div class="space-y-2">
                            <div class="text-xs font-semibold text-gray-700">
                                CPU Usage <span class="float-right">100%</span>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-gray-200">
                                <div class="h-1.5 w-full rounded-full bg-green-500" />
                            </div>
                            <div class="text-xs font-semibold text-gray-700">
                                Memory <span class="float-right">70%</span>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-gray-200">
                                <div class="h-1.5 w-[70%] rounded-full bg-green-500" />
                            </div>
                        </div>
                    </div>

                    <UButton block color="neutral" variant="outline" label="View Details" />
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="mb-3 text-sm font-bold text-gray-800">Quick Actions</div>

                    <div class="space-y-2">
                        <UButton block color="neutral" variant="outline" label="Create New User" />
                        <UButton
                            block
                            color="neutral"
                            variant="outline"
                            label="Manage WhatsApp Integrations"
                        />
                        <UButton block color="neutral" variant="outline" label="View Reports" />
                        <UButton block color="neutral" variant="outline" label="System Settings" />
                    </div>
                </div>
            </div>
        </div>
    </CmpLayout>
</template>
