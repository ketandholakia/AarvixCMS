<div class="bg-indigo-600 dark:bg-indigo-900/50 rounded-3xl p-8 sm:p-12 mt-16 text-center">
    <h3 class="text-3xl font-heading font-bold text-white mb-4">Subscribe to our Newsletter</h3>
    <p class="text-indigo-100 mb-8 max-w-2xl mx-auto">Get the latest articles, updates, and resources delivered straight to your inbox. No spam, ever.</p>

    @if(session('newsletter_success'))
        <div class="bg-green-500/20 text-green-100 p-4 rounded-xl max-w-md mx-auto mb-6">
            {{ session('newsletter_success') }}
        </div>
    @endif

    <form action="{{ route('newsletter.subscribe') }}" method="POST" class="max-w-md mx-auto relative flex flex-col sm:flex-row gap-3">
        @csrf
        <input type="hidden" name="source" value="footer_form">
        
        <!-- Honeypot -->
        <div style="display:none;">
            <input type="text" name="hp_contact" value="">
        </div>

        <input type="email" name="email" required placeholder="Enter your email address..." class="flex-1 w-full px-5 py-3 rounded-xl border-0 focus:ring-2 focus:ring-white bg-white/10 text-white placeholder-indigo-200">
        
        <button type="submit" class="px-6 py-3 bg-white text-indigo-600 font-bold rounded-xl hover:bg-gray-100 transition-colors shadow-sm">
            Subscribe
        </button>
    </form>
    
    @error('email')
        <p class="text-red-200 text-sm mt-3">{{ $message }}</p>
    @enderror
</div>
