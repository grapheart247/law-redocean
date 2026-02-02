<?php
/**
 * RedOcean Services - Dynamic FAQ Page
 * Content is managed via an array for easy updates.
 */

$faq_categories = [
    [
        'title' => 'Taxation & FBR Compliance',
        'icon' => 'fa-solid fa-file-invoice-dollar',
        'questions' => [
            [
                'q' => 'What documents are required for NTN registration?',
                'a' => 'For individuals, we typically need a copy of your CNIC, a registered mobile number, an email address, and proof of business premises (utility bill or tenancy agreement).'
            ],
            [
                'q' => 'When is the deadline for annual income tax filing?',
                'a' => 'For most individuals and salaried persons, the deadline is September 30th each year. For companies, it depends on their specific tax year closing.'
            ]
        ]
    ],
    [
        'title' => 'Business & SECP Registration',
        'icon' => 'fa-solid fa-building',
        'questions' => [
            [
                'q' => 'How long does it take to register a Private Limited Company?',
                'a' => 'With complete documentation, SECP registration usually takes 3 to 7 working days. This includes name reservation and issuance of the digital certificate.'
            ],
            [
                'q' => 'Can a foreigner start a business in Pakistan?',
                'a' => 'Yes, foreigners can incorporate a company in Pakistan. However, it involves additional security clearance steps from the Ministry of Interior.'
            ]
        ]
    ],
    [
        'title' => 'Working with RedOcean',
        'icon' => 'fa-solid fa-handshake',
        'questions' => [
            [
                'q' => 'Are your fees fixed or hourly?',
                'a' => 'We generally work on a fixed-fee basis for standard filings and registrations. For complex litigation or custom legal advisory, we provide a detailed quote based on the scope of work.'
            ],
            [
                'q' => 'Do you offer online consultations?',
                'a' => 'Yes, we offer consultations via Zoom, WhatsApp, or Google Meet to serve clients across Pakistan and overseas.'
            ]
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Frequently Asked Questions - RedOcean Services. Find answers to legal and tax consultancy queries.">
    <title>FAQ | RedOcean Services</title>

    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome 6 (CDN) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom Config for Brand Colors -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            dark: '#0f172a',
                            primary: '#334155',
                            accent: '#65a30d',
                            light: '#f1f5f9',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .glass-nav {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .faq-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            transition: all 0.3s ease;
        }
        .faq-card:hover {
            border-color: #65a30d;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        details summary::-webkit-details-marker {
            display: none;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">

    <!-- Navigation -->
    <nav class="fixed w-full z-50 glass-nav">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <a href="index.html" class="flex-shrink-0 flex items-center gap-3">
                    <div class="w-10 h-10 bg-brand-dark text-brand-accent rounded-lg flex items-center justify-center text-xl font-bold">
                        <i class="fa-solid fa-scale-balanced"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-brand-dark tracking-tight leading-none">RedOcean</h1>
                        <span class="text-xs text-brand-accent font-semibold tracking-wider uppercase">Legal & Tax Services</span>
                    </div>
                </a>
                <div class="flex items-center">
                    <a href="index.html" class="text-slate-600 hover:text-brand-accent font-medium flex items-center gap-2 transition">
                        <i class="fa-solid fa-arrow-left"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header Section -->
    <header class="pt-32 pb-12 bg-white border-b border-slate-200">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h1 class="text-3xl md:text-5xl font-bold text-brand-dark mb-4">Frequently Asked Questions</h1>
            <p class="text-slate-500 max-w-2xl mx-auto">Have questions about tax filing, company registration, or legal compliance? Find the answers to the most common queries below.</p>
        </div>
    </header>

    <!-- Content Section -->
    <main class="py-16 max-w-4xl mx-auto px-4">
        
        <div class="space-y-6">
            
            <?php foreach ($faq_categories as $category): ?>
                <div class="mb-10">
                    <h2 class="text-lg font-bold text-brand-dark mb-6 flex items-center gap-2">
                        <i class="<?php echo $category['icon']; ?> text-brand-accent"></i>
                        <?php echo htmlspecialchars($category['title']); ?>
                    </h2>
                    
                    <div class="space-y-4">
                        <?php foreach ($category['questions'] as $item): ?>
                            <details class="faq-card group">
                                <summary class="flex items-center justify-between p-6 cursor-pointer list-none">
                                    <span class="font-semibold text-slate-700"><?php echo htmlspecialchars($item['q']); ?></span>
                                    <span class="transition group-open:rotate-180">
                                        <i class="fa-solid fa-chevron-down text-slate-400"></i>
                                    </span>
                                </summary>
                                <div class="px-6 pb-6 text-slate-600 text-sm leading-relaxed">
                                    <?php echo htmlspecialchars($item['a']); ?>
                                </div>
                            </details>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>

        <!-- Contact CTA -->
        <div class="mt-16 p-8 bg-brand-dark rounded-2xl text-center">
            <h3 class="text-white text-xl font-bold mb-2">Still have questions?</h3>
            <p class="text-slate-400 mb-6">Our legal team is ready to assist you with your specific needs.</p>
            <a href="send_message.php" class="inline-flex items-center gap-2 bg-brand-accent hover:bg-lime-500 text-white font-bold py-3 px-8 rounded-lg transition transform hover:-translate-y-1">
                <i class="fa-solid fa-paper-plane"></i> Contact Support
            </a>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-brand-dark text-slate-400 py-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-sm">&copy; <?php echo date('Y'); ?> NoorGee Enterprise. All rights reserved.</p>
            <div class="mt-4 flex justify-center gap-6 text-xs uppercase tracking-widest">
                <a href="index.html" class="hover:text-white transition">Home</a>
                <a href="terms-and-conditions.html" class="hover:text-white transition">Terms</a>
                <a href="faq.php" class="text-white font-bold">FAQ</a>
            </div>
        </div>
    </footer>

</body>
</html>
