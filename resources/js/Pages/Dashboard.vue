<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';
import axios from 'axios';

const props = defineProps({
    stats: Object,
    viewTasks: Object,
    availableAccountsCount: Number,
    telegramPostUrlFilter: String
});

const form = useForm({
    telegram_post_url: '',
    views_count: 1
});

const message = ref('');
const messageType = ref('');

// Фильтр по ссылке на пост
const filter = ref(props.telegramPostUrlFilter || '');

// Создаем реактивную копию данных о просмотрах
const viewTasks = reactive({
    data: props.viewTasks.data,
    current_page: props.viewTasks.current_page,
    last_page: props.viewTasks.last_page,
    total: props.viewTasks.total,
    from: props.viewTasks.from,
    to: props.viewTasks.to,
    prev_page_url: props.viewTasks.prev_page_url,
    next_page_url: props.viewTasks.next_page_url,
    path: props.viewTasks.path
});

const submitViews = async () => {
    // Клиентская валидация
    if (form.views_count > props.availableAccountsCount) {
        message.value = `Максимальное количество просмотров: ${props.availableAccountsCount} (доступно аккаунтов)`;
        messageType.value = 'error';
        setTimeout(() => {
            message.value = '';
        }, 5000);
        return;
    }

    if (props.availableAccountsCount === 0) {
        message.value = 'Нет доступных аккаунтов для добавления просмотров';
        messageType.value = 'error';
        setTimeout(() => {
            message.value = '';
        }, 5000);
        return;
    }

    try {
        const response = await axios.post('/dashboard/add-views', {
            telegram_post_url: form.telegram_post_url,
            views_count: form.views_count
        });
        
        if (response.data.success) {
            message.value = response.data.message;
            messageType.value = 'success';
            form.reset();
            
            // Обновляем список просмотров
            await refreshViewTasks();
        }
    } catch (error) {
        message.value = error.response?.data?.message || 'Произошла ошибка';
        messageType.value = 'error';
    }
    
    setTimeout(() => {
        message.value = '';
    }, 5000);
};

const refreshViewTasks = async () => {
    try {
        const response = await axios.get('/dashboard/view-tasks', {
            params: {
                telegram_post_url: filter.value || undefined
            }
        });
        if (response.data.success) {
            viewTasks.data = response.data.viewTasks.data;
            viewTasks.current_page = response.data.viewTasks.current_page;
            viewTasks.last_page = response.data.viewTasks.last_page;
            viewTasks.total = response.data.viewTasks.total;
            viewTasks.from = response.data.viewTasks.from;
            viewTasks.to = response.data.viewTasks.to;
            viewTasks.prev_page_url = response.data.viewTasks.prev_page_url;
            viewTasks.next_page_url = response.data.viewTasks.next_page_url;
            viewTasks.path = response.data.viewTasks.path;
        }
    } catch (error) {
        console.error('Ошибка при обновлении списка просмотров:', error);
    }
};

const resetFilters = async () => {
    filter.value = '';
    try {
        await refreshViewTasks();
    } finally {
        if (typeof window !== 'undefined' && window.history && window.history.replaceState) {
            try {
                window.history.replaceState({}, '', '/dashboard');
            } catch (e) {}
        }
    }
};

const getPageNumbers = () => {
    const currentPage = viewTasks.current_page;
    const lastPage = viewTasks.last_page;
    const pages = [];
    
    if (lastPage <= 7) {
        for (let i = 1; i <= lastPage; i++) {
            pages.push(i);
        }
    } else {
        if (currentPage <= 4) {
            for (let i = 1; i <= 5; i++) {
                pages.push(i);
            }
            pages.push('...');
            pages.push(lastPage);
        } else if (currentPage >= lastPage - 3) {
            pages.push(1);
            pages.push('...');
            for (let i = lastPage - 4; i <= lastPage; i++) {
                pages.push(i);
            }
        } else {
            pages.push(1);
            pages.push('...');
            for (let i = currentPage - 1; i <= currentPage + 1; i++) {
                pages.push(i);
            }
            pages.push('...');
            pages.push(lastPage);
        }
    }
    
    return pages;
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
                                    :max="availableAccountsCount"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                />
                                <p class="mt-1 text-sm text-gray-500">
                                    Максимум: {{ availableAccountsCount }} (доступно аккаунтов)
                                </p>
                            </div>
                            
                            <button 
                                type="submit" 
                                :disabled="form.processing || availableAccountsCount === 0"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                            >
                                {{ form.processing ? 'Обработка...' : (availableAccountsCount === 0 ? 'Нет доступных аккаунтов' : 'Добавить просмотры') }}
                            </button>
                            
                            <div v-if="availableAccountsCount === 0" class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-700">
                                            Нет активных аккаунтов для добавления просмотров. Добавьте аккаунты в разделе 
                                            <a href="/accounts/manage" class="font-medium underline hover:text-yellow-600">
                                                "Управление аккаунтами"
                                            </a>.
                                        </p>
                                    </div>
                                </div>
                            </div>
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

                <!-- Список добавленных просмотров -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Добавленные просмотры</h3>
                        
                        <!-- Фильтр по ссылке на пост -->
                        <div class="mb-4 grid grid-cols-1 sm:grid-cols-6 gap-3">
                            <div class="sm:col-span-4">
                                <input
                                    type="text"
                                    v-model="filter"
                                    placeholder="Фильтр по ссылке на пост"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                            <div class="sm:col-span-2 flex gap-2 flex-wrap sm:flex-nowrap sm:justify-end">
                                <button
                                    type="button"
                                    @click="refreshViewTasks"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >Искать</button>
                                <button
                                    type="button"
                                    @click="resetFilters"
                                    class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 transition ease-in-out duration-150"
                                >Сбросить</button>
                            </div>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ссылка на пост</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Просмотров</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Кто создал</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Когда создано</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="task in viewTasks.data" :key="task.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600">
                                            <a :href="task.telegram_post_url" target="_blank" class="hover:text-blue-800 hover:underline">
                                                {{ task.telegram_post_url.length > 50 ? task.telegram_post_url.substring(0, 50) + '...' : task.telegram_post_url }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">{{ task.views_count }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ task.user.name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ new Date(task.created_at).toLocaleString('ru-RU') }}
                                        </td>
                                    </tr>
                                    <tr v-if="viewTasks.data.length === 0">
                                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                            Задачи не найдены
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Пагинация -->
                        <div v-if="viewTasks.last_page > 1" class="mt-6 flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <a v-if="viewTasks.prev_page_url" 
                                   :href="viewTasks.prev_page_url"
                                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Назад
                                </a>
                                <a v-if="viewTasks.next_page_url"
                                   :href="viewTasks.next_page_url"
                                   class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Далее
                                </a>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Показано <span class="font-medium">{{ viewTasks.from }}</span> до <span class="font-medium">{{ viewTasks.to }}</span>
                                        из <span class="font-medium">{{ viewTasks.total }}</span> результатов
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        <a v-if="viewTasks.prev_page_url" 
                                           :href="`${viewTasks.prev_page_url}${filter ? `&telegram_post_url=${encodeURIComponent(filter)}` : ''}`"
                                           class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            ‹
                                        </a>
                                        <span v-for="page in getPageNumbers()" :key="page">
                                            <a v-if="page !== '...'"
                                               :href="`${viewTasks.path}?page=${page}${filter ? `&telegram_post_url=${encodeURIComponent(filter)}` : ''}`"
                                               :class="{
                                                   'relative inline-flex items-center px-4 py-2 border text-sm font-medium': true,
                                                   'z-10 bg-indigo-50 border-indigo-500 text-indigo-600': page === viewTasks.current_page,
                                                   'bg-white border-gray-300 text-gray-500 hover:bg-gray-50': page !== viewTasks.current_page
                                               }">
                                                {{ page }}
                                            </a>
                                            <span v-else class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                                ...
                                            </span>
                                        </span>
                                        <a v-if="viewTasks.next_page_url"
                                           :href="`${viewTasks.next_page_url}${filter ? `&telegram_post_url=${encodeURIComponent(filter)}` : ''}`"
                                           class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            ›
                                        </a>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
