<?php
/**
 * AI Assistant Panel Template
 * Staff-only AI assistant for analyzing tickets
 *
 * Variables:
 * - $ticket: Current ticket object
 * - $thisstaff: Current staff member
 */

// Security check - staff only
if (!$thisstaff) {
    return;
}

// Check if ticket exists and staff has permission
if (!$ticket || !$ticket->checkStaffPerm($thisstaff)) {
    return;
}

$ticket_id = $ticket->getId();
?>

<div id="ai-assistant-panel" class="ai-assistant-container" data-ticket-id="<?php echo $ticket_id; ?>">
    <div class="ai-assistant-header" onclick="toggleAIAssistant()">
        <h3>
            <i class="icon-lightbulb"></i>
            <span><?php echo __('AI Assistant'); ?></span>
            <span class="badge ai-badge">AI</span>
        </h3>
        <i class="icon-chevron-down toggle-icon" id="ai-toggle-icon"></i>
    </div>

    <div class="ai-assistant-content" id="ai-assistant-content" style="display: none;">
        <!-- Status indicator -->
        <div id="ai-status-message" class="ai-status-message" style="display: none;"></div>

        <!-- Question input area -->
        <div class="ai-question-section">
            <div class="ai-input-wrapper">
                <label for="ai-question"><?php echo __('Ask a question about this ticket:'); ?></label>
                <textarea
                    id="ai-question"
                    class="ai-question-input"
                    rows="3"
                    placeholder="<?php echo __('Example: What is the main issue? What solutions were attempted? What is the current status?'); ?>"
                ></textarea>
            </div>

            <!-- Example questions -->
            <div class="ai-examples">
                <small><?php echo __('Quick questions:'); ?></small>
                <div class="ai-example-buttons">
                    <button type="button" class="ai-example-btn" onclick="setAIQuestion(this)" data-question="What is the main issue in this ticket?">
                        <?php echo __('Main issue'); ?>
                    </button>
                    <button type="button" class="ai-example-btn" onclick="setAIQuestion(this)" data-question="What solutions have been attempted?">
                        <?php echo __('Solutions attempted'); ?>
                    </button>
                    <button type="button" class="ai-example-btn" onclick="setAIQuestion(this)" data-question="What are the next recommended steps?">
                        <?php echo __('Next steps'); ?>
                    </button>
                    <button type="button" class="ai-example-btn" onclick="setAIQuestion(this)" data-question="Summarize this ticket conversation.">
                        <?php echo __('Summarize'); ?>
                    </button>
                </div>
            </div>

            <!-- Submit button -->
            <div class="ai-submit-wrapper">
                <button type="button" id="ai-submit-btn" class="button ai-submit-btn" onclick="askAIAssistant()">
                    <i class="icon-magic"></i>
                    <?php echo __('Ask AI Assistant'); ?>
                </button>
            </div>
        </div>

        <!-- Response area -->
        <div id="ai-response-section" class="ai-response-section" style="display: none;">
            <div class="ai-response-header">
                <h4><i class="icon-comment-alt"></i> <?php echo __('AI Response'); ?></h4>
                <div class="ai-response-actions">
                    <button type="button" class="button ai-copy-btn" onclick="copyAIResponse()" title="<?php echo __('Copy response'); ?>">
                        <i class="icon-copy"></i>
                        <?php echo __('Copy'); ?>
                    </button>
                    <button type="button" class="button ai-clear-btn" onclick="clearAIResponse()" title="<?php echo __('Clear response'); ?>">
                        <i class="icon-remove"></i>
                        <?php echo __('Clear'); ?>
                    </button>
                </div>
            </div>
            <div id="ai-response-content" class="ai-response-content">
                <!-- AI response will be inserted here -->
            </div>
            <div class="ai-response-meta">
                <small id="ai-response-timestamp"></small>
            </div>
        </div>

        <!-- Loading indicator -->
        <div id="ai-loading" class="ai-loading" style="display: none;">
            <div class="ai-loading-spinner">
                <i class="icon-spinner icon-spin"></i>
                <span><?php echo __('Analyzing ticket...'); ?></span>
            </div>
        </div>

        <!-- Disclaimer -->
        <div class="ai-disclaimer">
            <small>
                <i class="icon-info-circle"></i>
                <?php echo __('AI responses are generated automatically and should be reviewed before use. This feature analyzes ticket history to provide suggestions.'); ?>
            </small>
        </div>
    </div>
</div>

<script type="text/javascript">
// AI Assistant JavaScript functions are defined in ai-assistant.js
// This script block ensures the panel is initialized when loaded
$(document).ready(function() {
    if (typeof initAIAssistant === 'function') {
        initAIAssistant();
    }
});
</script>
