<template>
    <Head title="Управление аккаунтами" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Управление Telegram аккаунтами
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Форма добавления аккаунта -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Добавить новый аккаунт</h3>
                        
                        <form @submit.prevent="submitAccount" class="space-y-4" enctype="multipart/form-data">
                            <div>
                                <label for="session_data" class="block text-sm font-medium text-gray-700">
                                    Session Data файл
                                </label>
                                <input 
                                    type="file"
                                    id="session_data"
                                    ref="sessionDataInput"
                                    @change="handleSessionDataFile"
                                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                    accept=".session,.txt,.dat"
                                    required
                                />
                                <p class="mt-1 text-sm text-gray-500">
                                    Загрузите файл с session данными Telegram аккаунта
                                </p>
                                <div v-if="form.session_data" class="mt-2 text-sm text-green-600">
                                    ✓ Файл выбран: {{ form.session_data.name }} ({{ formatFileSize(form.session_data.size) }})
                                </div>
                            </div>
                            
                            <div>
                                <label for="json_data" class="block text-sm font-medium text-gray-700">
                                    JSON Data файл
                                </label>
                                <input 
                                    type="file"
                                    id="json_data"
                                    ref="jsonDataInput"
                                    @change="handleJsonDataFile"
                                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                    accept=".json,.txt"
                                    required
                                />
                                <p class="mt-1 text-sm text-gray-500">
                                    Загрузите JSON файл с данными аккаунта (app_id, app_hash, phone и т.д.)
                                </p>
                                <div v-if="form.json_data" class="mt-2 text-sm text-green-600">
                                    ✓ Файл выбран: {{ form.json_data.name }} ({{ formatFileSize(form.json_data.size) }})
                                </div>
                            </div>
                            
                            <button 
                                type="submit" 
                                :disabled="loading"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                            >
                                {{ loading ? 'Добавление...' : 'Добавить аккаунт' }}
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

                <!-- Список аккаунтов -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Список аккаунтов</h3>
                            <button 
                                @click="loadAccounts"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Обновить
                            </button>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Прокси</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Использований</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Последнее использование</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="account in accounts" :key="account.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ account.id }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ account.proxy_id || '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ account.usage_count }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ account.last_used_at ? new Date(account.last_used_at).toLocaleString('ru-RU') : 'Никогда' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="{
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium': true,
                                                'bg-green-100 text-green-800': account.is_active,
                                                'bg-red-100 text-red-800': !account.is_active
                                            }">
                                                {{ account.is_active ? 'Активен' : 'Неактивен' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button 
                                                v-if="account.is_active"
                                                @click="deactivateAccount(account.id)"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                Деактивировать
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="accounts.length === 0">
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            Аккаунты не найдены
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

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';

const form = ref({
    session_data: null,
    json_data: null
});
const accounts = ref([]);
const loading = ref(false);
const message = ref('');
const messageType = ref('');

const showMessage = (msg, type) => {
    message.value = msg;
    messageType.value = type;
    setTimeout(() => {
        message.value = '';
    }, 5000);
};

const handleSessionDataFile = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.value.session_data = file;
    }
};

const handleJsonDataFile = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.value.json_data = file;
    }
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const submitAccount = async () => {
    loading.value = true;
    
    try {
        if (!form.value.session_data || !form.value.json_data) {
            showMessage('Пожалуйста, выберите оба файла', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('session_data', form.value.session_data);
        formData.append('json_data', form.value.json_data);
        
        const response = await axios.post('/accounts', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        
        if (response.data.success) {
            showMessage(response.data.message, 'success');
            // Очищаем форму
            form.value.session_data = null;
            form.value.json_data = null;
            // Сбрасываем input файлы
            if (document.getElementById('session_data')) {
                document.getElementById('session_data').value = '';
            }
            if (document.getElementById('json_data')) {
                document.getElementById('json_data').value = '';
            }
            await loadAccounts();
        }
    } catch (error) {
        showMessage(error.response?.data?.message || 'Произошла ошибка', 'error');
    } finally {
        loading.value = false;
    }
};

const loadAccounts = async () => {
    try {
        const response = await axios.get('/accounts');
        if (response.data.success) {
            accounts.value = response.data.accounts;
        }
    } catch (error) {
        showMessage('Ошибка при загрузке аккаунтов', 'error');
    }
};

const deactivateAccount = async (accountId) => {
    if (!confirm('Вы уверены, что хотите деактивировать этот аккаунт?')) {
        return;
    }
    
    try {
        const response = await axios.patch(`/accounts/${accountId}/deactivate`);
        if (response.data.success) {
            showMessage(response.data.message, 'success');
            await loadAccounts();
        }
    } catch (error) {
        showMessage('Ошибка при деактивации аккаунта', 'error');
    }
};

onMounted(() => {
    loadAccounts();
});
</script>
