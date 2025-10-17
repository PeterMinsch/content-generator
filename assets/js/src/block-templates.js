/**
 * Block Templates for Live Preview
 *
 * Provides HTML templates for each block type with placeholder content.
 * Templates are used in the live preview iframe during CSV import.
 *
 * @package SEOGenerator
 */

/**
 * Get HTML template for a specific block type
 *
 * @param {string} blockType - The block type key (e.g., 'hero', 'faqs')
 * @return {string} HTML template string
 */
export function getBlockTemplate( blockType ) {
	const templates = {
		hero: `
			<section class="seo-block seo-block--hero">
				<h1>Discover the Perfect Engagement Ring</h1>
				<div class="hero-subtitle">Handcrafted with precision and elegance</div>
				<div class="hero-summary">
					<p>Our collection features stunning engagement rings crafted from the finest materials. Each piece is designed to celebrate your unique love story with timeless beauty and exceptional craftsmanship.</p>
				</div>
				<div class="hero-image">
					<img src="https://via.placeholder.com/1200x600/4A90E2/FFFFFF?text=Hero+Image" alt="Hero Image" />
				</div>
			</section>
		`,

		serp_answer: `
			<div class="preview-block serp-block">
				<div class="serp-box">
					<h3 class="serp-heading">What makes a quality engagement ring?</h3>
					<p class="serp-answer">A quality engagement ring combines expert craftsmanship, certified diamonds or gemstones, durable precious metals, and attention to detail. The best rings feature secure settings, proper proportions, and ethical sourcing practices.</p>
					<ul class="serp-bullets">
						<li>Certified diamond or gemstone quality</li>
						<li>Durable precious metal construction</li>
						<li>Expert craftsmanship and secure settings</li>
						<li>Ethical sourcing and transparency</li>
					</ul>
				</div>
			</div>
		`,

		product_criteria: `
			<div class="preview-block criteria-block">
				<h3 class="criteria-heading">Key Features to Consider</h3>
				<div class="criteria-grid">
					<div class="criteria-item">
						<span class="criteria-icon">✓</span>
						<div class="criteria-content">
							<strong>Metal Quality</strong>
							<p>Choose from platinum, 14k/18k gold, or alternative metals for durability and hypoallergenic properties.</p>
						</div>
					</div>
					<div class="criteria-item">
						<span class="criteria-icon">✓</span>
						<div class="criteria-content">
							<strong>Diamond Certification</strong>
							<p>Look for GIA or AGS certification to ensure quality, authenticity, and accurate grading of the 4 Cs.</p>
						</div>
					</div>
					<div class="criteria-item">
						<span class="criteria-icon">✓</span>
						<div class="criteria-content">
							<strong>Craftsmanship</strong>
							<p>Expert hand-finishing, secure prong settings, and attention to detail ensure lasting beauty.</p>
						</div>
					</div>
					<div class="criteria-item">
						<span class="criteria-icon">✓</span>
						<div class="criteria-content">
							<strong>Style & Design</strong>
							<p>Timeless designs that reflect personal taste while maintaining long-term appeal and wearability.</p>
						</div>
					</div>
				</div>
			</div>
		`,

		materials: `
			<div class="preview-block materials-block">
				<h3 class="materials-heading">Popular Metal Options</h3>
				<div class="materials-grid">
					<div class="material-card">
						<h4>Platinum</h4>
						<div class="material-pros">
							<strong>Pros:</strong> Extremely durable, hypoallergenic, naturally white color
						</div>
						<div class="material-cons">
							<strong>Cons:</strong> Higher cost, heavier weight
						</div>
						<div class="material-best">
							<strong>Best For:</strong> Daily wear, sensitive skin
						</div>
					</div>
					<div class="material-card">
						<h4>18K Gold</h4>
						<div class="material-pros">
							<strong>Pros:</strong> Classic appearance, warm tones, valuable
						</div>
						<div class="material-cons">
							<strong>Cons:</strong> Softer than platinum, requires maintenance
						</div>
						<div class="material-best">
							<strong>Best For:</strong> Traditional styles, color options
						</div>
					</div>
					<div class="material-card">
						<h4>Tungsten</h4>
						<div class="material-pros">
							<strong>Pros:</strong> Extremely scratch-resistant, affordable
						</div>
						<div class="material-cons">
							<strong>Cons:</strong> Cannot be resized, less traditional
						</div>
						<div class="material-best">
							<strong>Best For:</strong> Active lifestyles, budget-conscious
						</div>
					</div>
				</div>
			</div>
		`,

		process: `
			<div class="preview-block process-block">
				<h3 class="process-heading">Your Journey to the Perfect Ring</h3>
				<div class="process-steps">
					<div class="process-step">
						<div class="step-number">1</div>
						<div class="step-content">
							<h4>Choose Your Style</h4>
							<p>Browse our curated collection to find the ring style that speaks to you—from classic solitaires to vintage-inspired designs.</p>
						</div>
					</div>
					<div class="process-step">
						<div class="step-number">2</div>
						<div class="step-content">
							<h4>Select Your Metal</h4>
							<p>Choose from platinum, white gold, yellow gold, or rose gold to complement your personal style and lifestyle needs.</p>
						</div>
					</div>
					<div class="process-step">
						<div class="step-number">3</div>
						<div class="step-content">
							<h4>Customize Details</h4>
							<p>Work with our experts to personalize your ring with engravings, diamond selection, and sizing for the perfect fit.</p>
						</div>
					</div>
				</div>
			</div>
		`,

		comparison: `
			<div class="preview-block comparison-block">
				<h3 class="comparison-heading">Natural vs. Lab-Grown Diamonds</h3>
				<p class="comparison-intro">Understanding the key differences to make an informed choice</p>
				<div class="comparison-table">
					<div class="comparison-header">
						<div class="comparison-col">Attribute</div>
						<div class="comparison-col">Natural Diamond</div>
						<div class="comparison-col">Lab-Grown Diamond</div>
					</div>
					<div class="comparison-row">
						<div class="comparison-col">Origin</div>
						<div class="comparison-col">Mined from earth</div>
						<div class="comparison-col">Created in laboratory</div>
					</div>
					<div class="comparison-row">
						<div class="comparison-col">Chemical Composition</div>
						<div class="comparison-col">100% carbon</div>
						<div class="comparison-col">100% carbon (identical)</div>
					</div>
					<div class="comparison-row">
						<div class="comparison-col">Price</div>
						<div class="comparison-col">Higher cost</div>
						<div class="comparison-col">30-40% less expensive</div>
					</div>
					<div class="comparison-row">
						<div class="comparison-col">Environmental Impact</div>
						<div class="comparison-col">Mining required</div>
						<div class="comparison-col">Lower carbon footprint</div>
					</div>
					<div class="comparison-row">
						<div class="comparison-col">Rarity</div>
						<div class="comparison-col">Naturally rare</div>
						<div class="comparison-col">Reproducible</div>
					</div>
				</div>
			</div>
		`,

		product_showcase: `
			<div class="preview-block showcase-block">
				<h3 class="showcase-heading">Featured Collections</h3>
				<p class="showcase-intro">Explore our most popular engagement ring styles</p>
				<div class="showcase-grid">
					<div class="showcase-item">
						<div class="showcase-image" style="background-image: url('https://via.placeholder.com/300x300/E8E8E8/999999?text=Ring+1');"></div>
						<h4>Classic Solitaire</h4>
						<p class="showcase-sku">SKU: RING-001</p>
						<p class="showcase-desc">Timeless elegance with a brilliant center stone</p>
					</div>
					<div class="showcase-item">
						<div class="showcase-image" style="background-image: url('https://via.placeholder.com/300x300/E8E8E8/999999?text=Ring+2');"></div>
						<h4>Halo Setting</h4>
						<p class="showcase-sku">SKU: RING-002</p>
						<p class="showcase-desc">Center stone surrounded by brilliant accents</p>
					</div>
					<div class="showcase-item">
						<div class="showcase-image" style="background-image: url('https://via.placeholder.com/300x300/E8E8E8/999999?text=Ring+3');"></div>
						<h4>Vintage Inspired</h4>
						<p class="showcase-sku">SKU: RING-003</p>
						<p class="showcase-desc">Intricate details with old-world charm</p>
					</div>
				</div>
			</div>
		`,

		size_fit: `
			<div class="preview-block size-fit-block">
				<h3 class="size-heading">Ring Sizing Guide</h3>
				<div class="size-chart">
					<img src="https://via.placeholder.com/600x300/F5F5F5/666666?text=Ring+Size+Chart" alt="Ring size chart" class="size-chart-image">
				</div>
				<div class="comfort-fit">
					<h4>Comfort Fit Information</h4>
					<p>Our rings feature a comfort-fit interior with rounded edges for all-day wearability. Comfort-fit rings may feel slightly tighter than traditional flat bands, so we recommend trying on your size or consulting our sizing experts for the perfect fit.</p>
				</div>
			</div>
		`,

		care_warranty: `
			<div class="preview-block care-warranty-block">
				<div class="care-section">
					<h3 class="care-heading">Jewelry Care Instructions</h3>
					<ul class="care-list">
						<li>Clean with warm water and mild soap using a soft brush</li>
						<li>Remove jewelry before swimming, exercising, or manual work</li>
						<li>Store in a fabric-lined jewelry box away from other pieces</li>
						<li>Have prongs checked annually by a professional jeweler</li>
						<li>Avoid harsh chemicals, chlorine, and abrasive cleaners</li>
					</ul>
				</div>
				<div class="warranty-section">
					<h3 class="warranty-heading">Warranty Information</h3>
					<p class="warranty-text">All rings include our lifetime warranty covering manufacturing defects, structural integrity, and prong retipping. Free professional cleaning and inspection services are available at any of our locations. Normal wear and accidental damage are not covered but can be repaired for a reasonable fee.</p>
				</div>
			</div>
		`,

		ethics: `
			<div class="preview-block ethics-block">
				<h3 class="ethics-heading">Ethical Sourcing & Sustainability</h3>
				<div class="ethics-content">
					<p class="ethics-text">We are committed to ethical sourcing practices and environmental responsibility. All our diamonds are conflict-free and comply with the Kimberley Process. Our precious metals are sourced from certified refiners who follow responsible mining practices. We believe in transparency throughout our supply chain and work only with suppliers who share our commitment to ethical business practices.</p>
				</div>
				<div class="certifications">
					<h4>Our Certifications</h4>
					<div class="cert-badges">
						<div class="cert-badge">✓ Kimberley Process Certified</div>
						<div class="cert-badge">✓ Responsible Jewellery Council</div>
						<div class="cert-badge">✓ Fair Trade Certified</div>
					</div>
				</div>
			</div>
		`,

		faqs: `
			<div class="preview-block faqs-block">
				<h3 class="faqs-heading">Frequently Asked Questions</h3>
				<div class="faq-list">
					<div class="faq-item">
						<h4 class="faq-question">How do I know my ring size?</h4>
						<p class="faq-answer">Visit one of our locations for professional sizing, order a free ring sizer online, or use our printable sizing guide. For the most accurate fit, we recommend professional sizing as finger size can vary throughout the day.</p>
					</div>
					<div class="faq-item">
						<h4 class="faq-question">What is the difference between 14K and 18K gold?</h4>
						<p class="faq-answer">14K gold contains 58.3% pure gold mixed with alloy metals for durability, while 18K contains 75% pure gold for a richer color and softer feel. 14K is more durable for everyday wear, while 18K offers a more luxurious appearance.</p>
					</div>
					<div class="faq-item">
						<h4 class="faq-question">How should I care for my engagement ring?</h4>
						<p class="faq-answer">Clean your ring regularly with warm water and mild soap, remove it during physical activities, store it separately from other jewelry, and have it professionally inspected annually to ensure settings remain secure.</p>
					</div>
					<div class="faq-item">
						<h4 class="faq-question">Do you offer ring resizing?</h4>
						<p class="faq-answer">Yes, we offer complimentary resizing within the first year for rings purchased from us. Most rings can be sized up or down by 2-3 sizes. Some designs with stones around the entire band may have sizing limitations.</p>
					</div>
				</div>
			</div>
		`,

		cta: `
			<div class="preview-block cta-block">
				<div class="cta-content">
					<h3 class="cta-heading">Ready to Find Your Perfect Ring?</h3>
					<p class="cta-text">Visit our showroom to see our collection in person, or browse online to discover your ideal engagement ring. Our expert jewelers are here to guide you through every step of your journey.</p>
					<div class="cta-buttons">
						<button class="cta-btn cta-btn-primary">Browse Our Collection</button>
						<button class="cta-btn cta-btn-secondary">Schedule a Consultation</button>
					</div>
				</div>
			</div>
		`,

		about_section: `
			<section class="seo-block seo-block--about-section">
				<div class="about-section__content">
					<header class="about-section__header">
						<h2 class="about-section__heading">About Bravo Jewelers</h2>
						<p class="about-section__description">Family-run and handcrafted in Carlsbad, Bravo Jewelers has over 25 years of experience serving San Diego County with timeless craftsmanship.</p>
					</header>
					<div class="about-section__features">
						<div class="about-section__feature">
							<div class="about-section__feature-icon">
								<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M25.2419 11.5509H21.8947V7.21924H6.27444C5.04713 7.21924 4.04297 8.19387 4.04297 9.38508V21.2972H6.27444C6.27444 23.0948 7.76952 24.546 9.62164 24.546C11.4738 24.546 12.9689 23.0948 12.9689 21.2972H19.6633C19.6633 23.0948 21.1583 24.546 23.0105 24.546C24.8626 24.546 26.3577 23.0948 26.3577 21.2972H28.5891V15.8826L25.2419 11.5509Z" fill="currentColor"/></svg>
							</div>
							<div class="about-section__feature-content">
								<h3 class="about-section__feature-title">Free Shipping</h3>
								<p class="about-section__feature-desc">On orders over $500</p>
							</div>
						</div>
						<div class="about-section__feature">
							<div class="about-section__feature-icon">
								<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M25.333 10.6668L19.9997 16.0002H23.9997C23.9997 20.4135 20.413 24.0002 15.9997 24.0002C14.653 24.0002 13.373 23.6668 12.2663 23.0668L10.3197 25.0135C11.9597 26.0535 13.9063 26.6668 15.9997 26.6668C21.893 26.6668 26.6663 21.8935 26.6663 16.0002H30.6663L25.333 10.6668Z" fill="currentColor"/></svg>
							</div>
							<div class="about-section__feature-content">
								<h3 class="about-section__feature-title">60-Days returns</h3>
								<p class="about-section__feature-desc">Hassle-free exchanges</p>
							</div>
						</div>
						<div class="about-section__feature">
							<div class="about-section__feature-icon">
								<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.9997 5.26255L24.296 8.94847V14.5188C24.296 19.8759 20.7641 24.8181 15.9997 26.2877C11.2352 24.8181 7.70338 19.8759 7.70338 14.5188V8.94847L15.9997 5.26255Z" fill="currentColor"/></svg>
							</div>
							<div class="about-section__feature-content">
								<h3 class="about-section__feature-title">lifetime warranty</h3>
								<p class="about-section__feature-desc">On all fine jewelry</p>
							</div>
						</div>
						<div class="about-section__feature">
							<div class="about-section__feature-icon">
								<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 7.6665H27.667C28.2191 7.66668 28.667 8.11433 28.667 8.6665V23.3335C28.6668 23.8855 28.219 24.3333 27.667 24.3335H5C4.44783 24.3335 4.00018 23.8856 4 23.3335V8.6665C4 8.11422 4.44772 7.6665 5 7.6665Z" stroke="currentColor" stroke-width="2"/></svg>
							</div>
							<div class="about-section__feature-content">
								<h3 class="about-section__feature-title">flexible financing</h3>
								<p class="about-section__feature-desc">0% APR available</p>
							</div>
						</div>
					</div>
				</div>
			</section>
		`,
	};

	// Return template or fallback for unknown block type.
	return (
		templates[ blockType ] ||
		`
		<div class="preview-block unknown-block">
			<p class="unknown-message">Block type: <strong>${ blockType }</strong></p>
			<p class="unknown-hint">Template not yet created for this block type.</p>
		</div>
	`
	);
}

// Make available globally for block-preview.js.
window.getBlockTemplate = getBlockTemplate;

export default getBlockTemplate;
