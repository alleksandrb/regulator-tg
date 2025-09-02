<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    stats: Object
});

const form = useForm({
    telegram_post_url: '',
    views_count: 1
});

const message = ref('');
const messageType = ref('');

const submitViews = async () => {
    try {
        const response = await axios.post('/dashboard/add-views', {
            telegram_post_url: form.telegram_post_url,
            views_count: form.views_count
        });
        
        if (response.data.success) {
            message.value = response.data.message;
            messageType.value = 'success';
            form.reset();
        }
    } catch (error) {
        message.value = error.response?.data?.message || 'Произошла ошибка';
        messageType.value = 'error';
    }
    
    setTimeout(() => {
        message.value = '';
    }, 5000);
};
</script>

<template>
    <Head title="Панель управления" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Панель управления
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Статистика -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-500">Всего аккаунтов</div>
                        <div class="text-2xl font-bold text-gray-900">{{ stats.total }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-500">Активных</div>
                        <div class="text-2xl font-bold text-green-600">{{ stats.active }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-500">Неактивных</div>
                        <div class="text-2xl font-bold text-red-600">{{ stats.inactive }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-500">Статус системы</div>
                        <div class="text-sm font-bold text-green-600">Работает</div>
                    </div>
                </div>

                <!-- Добавить просмотры -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Добавить просмотры</h3>
                        
                        <form @submit.prevent="submitViews" class="space-y-4">
                            <div>
                                <label for="telegram_post_url" class="block text-sm font-medium text-gray-700">
                                    Ссылка на пост Telegram
                                </label>
                                <input 
                                    type="url" 
                                    id="telegram_post_url"
                                    v-model="form.telegram_post_url"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="https://t.me/channel/123"
                                    required
                                />
                            </div>
                            
                            <div>
                                <label for="views_count" class="block text-sm font-medium text-gray-700">
                                    Количество просмотров
                                </label>
                                <input 
                                    type="number" 
                                    id="views_count"
                                    v-model="form.views_count"
                                    min="1"
                                    max="1000"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                />
                            </div>
                            
                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                            >
                                {{ form.processing ? 'Обработка...' : 'Добавить просмотры' }}
                            </button>
                        </form>

                        <!-- Сообщения -->
                        <div v-if="message" :class="{
                            'mt-4 p-4 rounded-md': true,
                            'bg-green-50 text-green-800 border border-green-200': messageType === 'success',
                            'bg-red-50 text-red-800 border border-red-200': messageType === 'error'
                        }">
                            {{ message }}
                        </div>
                    </div>
                </div>

                <!-- Топ используемых аккаунтов -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Топ используемых аккаунтов</h3>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Использований</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Последнее использование</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="account in stats.top_used" :key="account.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ account.id }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ account.usage_count }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ account.last_used_at ? new Date(account.last_used_at).toLocaleString('ru-RU') : 'Никогда' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
