<template>
  <div class="container-wrapper">
    <div class="login-card">
      <h1 class="title">Login User (Vue Tester)</h1>
      <p class="api-info">POST to <code class="api-code">{{ API_URL }}</code>
      </p>

      <form @submit.prevent="handleSubmit" class="form-space">

        <div>
          <label for="email" class="label-style">Email Address</label>
          <input type="email" id="email" name="email" placeholder="user@example.com" v-model="formData.email" required
            class="input-style" />
        </div>

        <div>
          <label for="password" class="label-style">Password</label>
          <input type="password" id="password" name="password" placeholder="********" v-model="formData.password"
            required class="input-style" />
        </div>

        <button type="submit" :disabled="loading" class="button-style">
          <template v-if="loading">
            <div class="loading-content">
              <svg class="spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="spinner-path-1" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="spinner-path-2" fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
              </svg>
              Logging In...
            </div>
          </template>
          <template v-else>
            Log In
          </template>
        </button>
      </form>

      <div v-if="hasErrors" class="alert-error" role="alert">
        <p class="alert-title">Login Failed</p>
        <ul class="error-list">
          <li v-for="(msg, index) in formatErrorMessages" :key="index">{{ msg }}</li>
        </ul>
      </div>

      <div v-if="isResponseVisible" :class="responseContainerClasses">
        <h3 class="response-title" :class="responseTextClasses">{{ responseTitle }}</h3>
        <pre class="response-pre">
    {{ JSON.stringify(responseData, null, 2) }}
  </pre>
      </div>
    </div>
  </div>
</template>

<script lang='ts'>
import { ref, computed } from 'vue';
import router from '@/router';
import { useAuthStore } from '@/stores/auth';



const API_URL = `${import.meta.env.VITE_API_URL}/login`;

interface FormData {
  email: string;
  password: string;
}

interface ApiResponse {
  status: number;
  isSuccess: boolean;
  data: any;
}

export default {
  // Composition API setup function
  setup() {
    const authStore = useAuthStore();
    // NOTE: Use credentials that you have successfully registered via the /api/register endpoint.
    const formData = ref<FormData>({
      email: 'testuser@dev.com',
      password: 'password123',
    });
    const loading = ref(false);
    const response = ref<ApiResponse | null>(null); // Full API response data
    const errors = ref<any>(null); // Specific validation errors map

    // Handles form submission and API call
    const handleSubmit = async () => {
      loading.value = true;
      response.value = null;
      errors.value = null;

      try {
        const res = await fetch(API_URL, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          // Send only email and password
          body: JSON.stringify({
            email: formData.value.email,
            password: formData.value.password,
          }),
        });

        const data = await res.json();

        if (res.ok) {
          // Success (HTTP 200) - login typically returns 200
          response.value = {
            status: res.status,
            isSuccess: true,
            data: data,
          };
          authStore.login(data.user, data.token);
          router.push({ name: 'welcome' })
        } else {
          // Failure (HTTP 4xx or 5xx)
          if (res.status === 422 && data.errors) {
            // Laravel Validation Errors (422 Unprocessable Content)
            errors.value = data.errors;
          } else {
            // Other API errors (e.g., 500 or 401/403 for wrong credentials)
            const errorMsg = data.message || `An unknown server error occurred. Status: ${res.status}`;
            errors.value = { general: [errorMsg] };
          }

          // Set non-success response state
          response.value = {
            status: res.status,
            isSuccess: false,
            data: data,
          };
        }

      } catch (error: any) {
        // Network failure or CORS issues
        errors.value = { network: [`Network error: Could not reach the API endpoint at ${API_URL}. Check if your Laravel server is running. (${error.message})`] };
      } finally {
        loading.value = false;
      }
    };

    // Helper to extract and flatten all error messages for display (Computed property)
    const formatErrorMessages = computed(() => {
      if (!errors.value) return [];
      let messages: string[] = [];
      for (const key in errors.value) {
        if (Array.isArray(errors.value[key])) {
          messages = messages.concat(errors.value[key]);
        }
      }
      return messages;
    });

    // Computed properties for UI state
    const hasErrors = computed(() => errors.value && Object.keys(errors.value).length > 0);
    const isResponseVisible = computed(() => !!response.value);
    const responseData = computed(() => response.value?.data || errors.value);

    const responseTitle = computed(() => response.value
      ? (response.value.isSuccess ? `Success! Status ${response.value.status} Logged In` : `Error! Status ${response.value.status}`)
      : 'API Response');

    const responseContainerClasses = computed(() => {
      if (!response.value) return 'response-container';
      return response.value.isSuccess
        ? 'response-container success-response'
        : 'response-container error-response-bg';
    });

    const responseTextClasses = computed(() => response.value?.isSuccess ? 'text-success' : 'text-error');


    return {
      formData,
      loading,
      errors,
      API_URL,
      handleSubmit,
      formatErrorMessages,
      hasErrors,
      isResponseVisible,
      responseContainerClasses,
      responseTitle,
      responseTextClasses,
      responseData,
    };
  }
}
</script>

<style>
/* ========================================
General Styles (Replaces min-h-screen, flex, bg-gray-100)
========================================
*/
body {
  margin: 0;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.container-wrapper {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  /* p-4 */
  background-color: #f7f7f7;
  /* bg-gray-100 */
}

/* ========================================
Login Card (Replaces w-full, max-w-md, bg-white, rounded-xl, shadow-2xl, p-6, md:p-8, border)
========================================
*/
.login-card {
  width: 100%;
  max-width: 28rem;
  /* max-w-md */
  background-color: white;
  border-radius: 1rem;
  /* rounded-xl */
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  /* shadow-2xl */
  padding: 1.5rem;
  /* p-6 */
  border: 1px solid #f3f4f6;
  /* border border-gray-100 */
}

@media (min-width: 768px) {
  .login-card {
    padding: 2rem;
    /* md:p-8 */
  }
}

/* ========================================
Header
========================================
*/
.title {
  font-size: 1.875rem;
  /* text-3xl */
  font-weight: 700;
  /* font-bold */
  text-align: center;
  /* text-center */
  color: #4f46e5;
  /* text-indigo-700 */
  margin-bottom: 0.5rem;
  /* mb-2 */
}

.api-info {
  font-size: 0.875rem;
  /* text-sm */
  text-align: center;
  /* text-center */
  color: #6b7280;
  /* text-gray-500 */
  margin-bottom: 1.5rem;
  /* mb-6 */
}

.api-code {
  font-size: 0.75rem;
  /* text-xs */
  color: #ec4899;
  /* text-pink-600 */
  font-family: monospace;
}

/* ========================================
Form and Inputs
========================================
*/
.form-space {
  margin-top: 1rem;
  /* space-y-4 (4*0.25rem = 1rem gap) */
}

.form-space>div {
  margin-bottom: 1rem;
}

.label-style {
  display: block;
  font-size: 0.875rem;
  /* text-sm */
  font-weight: 500;
  /* font-medium */
  color: #374151;
  /* text-gray-700 */
  margin-bottom: 0.25rem;
  /* mb-1 */
}

.input-style {
  width: 100%;
  padding: 0.5rem 1rem;
  /* px-4 py-2 */
  border: 1px solid #d1d5db;
  /* border border-gray-300 */
  border-radius: 0.5rem;
  /* rounded-lg */
  transition: border-color 150ms ease-in-out, box-shadow 150ms ease-in-out;
  /* transition duration-150 ease-in-out */
}

.input-style:focus {
  outline: 2px solid transparent;
  outline-offset: 2px;
  border-color: #4f46e5;
  /* focus:border-indigo-500 */
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.5);
  /* focus:ring-indigo-500 focus:ring-opacity-50 */
}

/* ========================================
Button
========================================
*/
.button-style {
  width: 100%;
  background-color: #4f46e5;
  /* bg-indigo-600 */
  color: white;
  padding: 0.625rem 1rem;
  /* py-2.5 */
  border-radius: 0.5rem;
  /* rounded-lg */
  font-weight: 600;
  /* font-semibold */
  border: none;
  cursor: pointer;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  /* shadow-md */
  transition: background-color 150ms ease-in-out, box-shadow 150ms ease-in-out;
  /* transition duration-150 ease-in-out */
}

.button-style:hover:not(:disabled) {
  background-color: #4338ca;
  /* hover:bg-indigo-700 */
}

.button-style:focus {
  outline: none;
  box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.5);
  /* focus:ring-4 focus:ring-indigo-500 focus:ring-opacity-50 */
}

.button-style:disabled {
  opacity: 0.5;
  /* disabled:opacity-50 */
  cursor: not-allowed;
  /* disabled:cursor-not-allowed */
}

.loading-content {
  display: flex;
  align-items: center;
  justify-content: center;
}

/* SVG Spinner */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}

.spinner {
  animation: spin 1s linear infinite;
  margin-right: 0.75rem;
  /* mr-3 */
  height: 1.25rem;
  /* h-5 */
  width: 1.25rem;
  /* w-5 */
  color: white;
  /* text-white */
  margin-left: -0.25rem;
  /* -ml-1 */
}

.spinner-path-1 {
  opacity: 0.25;
}

.spinner-path-2 {
  opacity: 0.75;
}


/* ========================================
Error Alert
========================================
*/
.alert-error {
  margin-top: 1rem;
  /* mt-4 */
  padding: 0.75rem;
  /* p-3 */
  background-color: #fef2f2;
  /* bg-red-100 */
  border: 1px solid #fca5a5;
  /* border border-red-400 */
  color: #b91c1c;
  /* text-red-700 */
  border-radius: 0.5rem;
  /* rounded-lg */
}

.alert-title {
  font-weight: 600;
  /* font-semibold */
}

.error-list {
  list-style-type: disc;
  list-style-position: inside;
  margin-top: 0.25rem;
  /* mt-1 */
  font-size: 0.875rem;
  /* text-sm */
  padding-left: 0;
}

/* ========================================
Response Area
========================================
*/
.response-container {
  margin-top: 1.5rem;
  /* mt-6 */
  padding: 1rem;
  /* p-4 */
  border-radius: 0.75rem;
  /* rounded-xl */
  box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
  /* shadow-inner */
}

.response-container.success-response {
  background-color: #f0fdf4;
  /* bg-green-50 */
  border: 1px solid #4ade80;
  /* border border-green-400 */
  color: #065f46;
  /* text-green-700 */
}

.response-container.error-response-bg {
  background-color: #fef2f2;
  /* bg-red-50 */
  border: 1px solid #fca5a5;
  /* border border-red-400 */
  color: #b91c1c;
  /* text-red-700 */
}

.response-title {
  font-weight: 600;
  /* font-semibold */
  margin-bottom: 0.5rem;
  /* mb-2 */
}

.text-success {
  color: #16a34a;
  /* text-green-800 */
}

.text-error {
  color: #991b1b;
  /* text-red-800 */
}

.response-pre {
  background-color: white;
  padding: 0.75rem;
  /* p-3 */
  border-radius: 0.375rem;
  /* rounded-md */
  font-size: 0.75rem;
  /* text-xs */
  overflow-x: auto;
  /* overflow-x-auto */
  color: #1f2937;
  /* text-gray-800 */
  border: 1px solid #e5e7eb;
  /* border border-gray-200 */
  white-space: pre-wrap;
  /* Ensures line breaks in JSON are visible */
}
</style>
