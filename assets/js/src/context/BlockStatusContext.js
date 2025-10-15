/**
 * BlockStatusContext
 *
 * React Context for managing block generation statuses.
 *
 * @package
 */

import {
	createContext,
	useContext,
	useState,
	useCallback,
	createElement,
} from '@wordpress/element';

const BlockStatusContext = createContext();

/**
 * Block types enum for all 12 content blocks.
 */
export const BLOCK_TYPES = {
	HERO: 'hero',
	SERP_ANSWER: 'serp_answer',
	PRODUCT_CRITERIA: 'product_criteria',
	MATERIALS: 'materials',
	PROCESS: 'process',
	COMPARISON: 'comparison',
	PRODUCT_SHOWCASE: 'product_showcase',
	SIZE_FIT: 'size_fit',
	CARE_WARRANTY: 'care_warranty',
	ETHICS: 'ethics',
	FAQS: 'faqs',
	CTA: 'cta',
};

/**
 * Determine initial status based on whether fields are populated.
 *
 * @param {Object} blockData Block field data.
 * @return {string} Initial status.
 */
const getInitialStatus = (blockData) => {
	if (!blockData || Object.keys(blockData).length === 0) {
		return 'not_generated';
	}

	// Check if any field has a value
	const hasContent = Object.values(blockData).some((value) => {
		if (Array.isArray(value)) {
			return value.length > 0;
		}
		return value && value !== '';
	});

	return hasContent ? 'generated' : 'not_generated';
};

/**
 * Initialize statuses from page data.
 *
 * @param {Object} pageData Page data with block fields.
 * @return {Object} Initial statuses object.
 */
const initializeStatuses = (pageData) => {
	const statuses = {};

	Object.values(BLOCK_TYPES).forEach((blockType) => {
		const blockData = pageData?.[blockType] || {};
		statuses[blockType] = getInitialStatus(blockData);
	});

	return statuses;
};

/**
 * BlockStatusProvider component.
 *
 * @param {Object}  props              Component props.
 * @param {Element} props.children     Child components.
 * @param {Object}  props.initialData  Initial page data.
 * @return {Element} Provider component.
 */
export const BlockStatusProvider = ({ children, initialData = {} }) => {
	const [statuses, setStatuses] = useState(() =>
		initializeStatuses(initialData)
	);
	const [errors, setErrors] = useState({});

	const updateBlockStatus = useCallback((blockType, status) => {
		setStatuses((prev) => ({ ...prev, [blockType]: status }));
	}, []);

	const setBlockError = useCallback((blockType, error) => {
		setErrors((prev) => ({ ...prev, [blockType]: error }));
	}, []);

	const clearBlockError = useCallback((blockType) => {
		setErrors((prev) => {
			const newErrors = { ...prev };
			delete newErrors[blockType];
			return newErrors;
		});
	}, []);

	const getBlockStatus = useCallback(
		(blockType) => statuses[blockType] || 'not_generated',
		[statuses]
	);

	const getBlockError = useCallback(
		(blockType) => errors[blockType] || null,
		[errors]
	);

	const value = {
		statuses,
		updateBlockStatus,
		getBlockStatus,
		setBlockError,
		clearBlockError,
		getBlockError,
	};

	return createElement(
		BlockStatusContext.Provider,
		{ value },
		children
	);
};

/**
 * Custom hook to use block status context.
 *
 * @return {Object} Block status context value.
 */
export const useBlockStatus = () => {
	const context = useContext(BlockStatusContext);

	if (!context) {
		throw new Error(
			'useBlockStatus must be used within BlockStatusProvider'
		);
	}

	return context;
};

export default BlockStatusContext;
