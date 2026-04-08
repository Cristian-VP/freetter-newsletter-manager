import { Head, usePage } from '@inertiajs/react';
import { type PageProps } from '@/types';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';

export default function Dashboard() {
    const { auth } = usePage<PageProps>().props;

    return (
        <AppSidebarLayout>
            <Head title="Dashboard" />

            <div className="space-y-6 p-8">
                {/* Welcome Section */}
                <div>
                    <h1 className="text-4xl font-bold text-gray-900">
                        Welcome back, {auth.user?.name}!
                    </h1>
                    <p className="mt-2 text-lg text-gray-600">
                        Here's what's happening with your Freetter workspace today.
                    </p>
                </div>

                {/* Quick Stats Grid */}
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                    {/* Stat Card Template */}
                    <div className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Posts</p>
                                <p className="mt-2 text-3xl font-bold text-gray-900">0</p>
                            </div>
                            <div className="rounded-full bg-blue-100 p-3">
                                <svg className="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C6.5 6.253 3 9.864 3 14.25m0 0c0 3.75 2.585 7.08 6.235 8.618m6.53-8.618C18.415 21.33 21 18 21 14.25m0 0c0-4.386-3.5-8-9-8.253" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Subscribers</p>
                                <p className="mt-2 text-3xl font-bold text-gray-900">0</p>
                            </div>
                            <div className="rounded-full bg-green-100 p-3">
                                <svg className="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Engagement</p>
                                <p className="mt-2 text-3xl font-bold text-gray-900">0%</p>
                            </div>
                            <div className="rounded-full bg-purple-100 p-3">
                                <svg className="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-600">Revenue</p>
                                <p className="mt-2 text-3xl font-bold text-gray-900">$0</p>
                            </div>
                            <div className="rounded-full bg-yellow-100 p-3">
                                <svg className="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Recent Activity Section */}
                <div className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 className="text-xl font-bold text-gray-900">Recent Activity</h2>
                    <p className="mt-4 text-center text-gray-500">
                        No activity yet. Start creating content to see your dashboard come alive!
                    </p>
                </div>

                {/* Get Started Section */}
                <div className="rounded-lg border border-blue-200 bg-blue-50 p-6">
                    <h3 className="text-lg font-bold text-blue-900">Getting Started</h3>
                    <p className="mt-2 text-blue-800">
                        Ready to launch your first post? Head over to the Publishing module to create and publish content.
                    </p>
                    <button className="mt-4 rounded-lg bg-blue-600 px-4 py-2 text-white font-medium hover:bg-blue-700 transition">
                        Create First Post
                    </button>
                </div>
            </div>
        </AppSidebarLayout>
    );
}
