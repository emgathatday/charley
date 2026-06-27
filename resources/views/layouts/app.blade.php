<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} | Charley</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-50 font-outfit text-gray-900 dark:bg-gray-900 dark:text-white/90">
    <div class="min-h-screen xl:flex">
        <aside class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-gray-200 bg-white px-5 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex h-[76px] items-center">
                <a href="{{ route('admin.dashboard.iam.users') }}" class="text-xl font-semibold text-gray-900 dark:text-white">
                    Charley Admin
                </a>
            </div>

            <nav class="flex flex-1 flex-col justify-between overflow-y-auto pb-6">
                <div>
                    <h2 class="mb-4 text-xs uppercase leading-[20px] text-gray-400">
                        Admin
                    </h2>

                    <ul class="flex flex-col gap-1">
                        <li>
                            <a href="{{ route('admin.dashboard.iam.users') }}"
                                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-theme-sm font-medium {{ request()->routeIs('admin.dashboard.iam.users') ? 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/[0.03]' }}">
                                <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M8.80443 5.60156C7.59109 5.60156 6.60749 6.58517 6.60749 7.79851C6.60749 9.01185 7.59109 9.99545 8.80443 9.99545C10.0178 9.99545 11.0014 9.01185 11.0014 7.79851C11.0014 6.58517 10.0178 5.60156 8.80443 5.60156ZM5.10749 7.79851C5.10749 5.75674 6.76267 4.10156 8.80443 4.10156C10.8462 4.10156 12.5014 5.75674 12.5014 7.79851C12.5014 9.84027 10.8462 11.4955 8.80443 11.4955C6.76267 11.4955 5.10749 9.84027 5.10749 7.79851ZM4.86252 15.3208C4.08769 16.0881 3.70377 17.0608 3.51705 17.8611C3.48384 18.0034 3.5211 18.1175 3.60712 18.2112C3.70161 18.3141 3.86659 18.3987 4.07591 18.3987H13.4249C13.6343 18.3987 13.7992 18.3141 13.8937 18.2112C13.9797 18.1175 14.017 18.0034 13.9838 17.8611C13.7971 17.0608 13.4132 16.0881 12.6383 15.3208C11.8821 14.572 10.6899 13.955 8.75042 13.955C6.81096 13.955 5.61877 14.572 4.86252 15.3208Z" />
                                </svg>
                                <span>IAM Users</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.dashboard.iam.verification-queue') }}"
                                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-theme-sm font-medium {{ request()->routeIs('admin.dashboard.iam.verification-queue') ? 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/[0.03]' }}">
                                <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M11.665 3.75621C11.8762 3.65064 12.1247 3.65064 12.3358 3.75621L18.7807 6.97856L12.3358 10.2009C12.1247 10.3065 11.8762 10.3065 11.665 10.2009L5.22014 6.97856L11.665 3.75621ZM4.29297 8.19203V16.0946C4.29297 16.3787 4.45347 16.6384 4.70757 16.7654L11.25 20.0366V11.6513C11.1631 11.6205 11.0777 11.5843 10.9942 11.5426L4.29297 8.19203Z" />
                                </svg>
                                <span>Verification Queue</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.dashboard.iam.user-security') }}"
                                class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-theme-sm font-medium {{ request()->routeIs('admin.dashboard.iam.user-security') ? 'bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/[0.03]' }}">
                                <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2.25C12.197 2.25 12.3859 2.32739 12.5262 2.46579C14.2431 4.15921 16.1918 5.25 18.25 5.25C18.6642 5.25 19 5.58579 19 6V10.25C19 15.3406 16.0786 19.1264 12.3038 20.7141C12.1094 20.7959 11.8906 20.7959 11.6962 20.7141C7.92143 19.1264 5 15.3406 5 10.25V6C5 5.58579 5.33579 5.25 5.75 5.25C7.80824 5.25 9.75689 4.15921 11.4738 2.46579C11.6141 2.32739 11.803 2.25 12 2.25Z" />
                                </svg>
                                <span>User Security</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-theme-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                        Logout
                    </button>
                </form>
            </nav>
        </aside>

        <div class="flex-1 ml-72">
            <header class="sticky top-0 z-30 border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 hidden">
                <div class="flex items-center justify-between px-4 py-4">
                    <a href="{{ route('admin.dashboard.iam.users') }}" class="text-lg font-semibold text-gray-900 dark:text-white">
                        Charley Admin
                    </a>
                    <nav class="flex items-center gap-3 text-theme-sm">
                        <a href="{{ route('admin.dashboard.iam.users') }}" class="text-gray-600 dark:text-gray-300">Users</a>
                        <a href="{{ route('admin.dashboard.iam.verification-queue') }}" class="text-gray-600 dark:text-gray-300">Verify</a>
                    </nav>
                </div>
            </header>

            <main class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
                @yield('content')
            </main>
        </div>
    </div>
</body>

@stack('scripts')

</html>


