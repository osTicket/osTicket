/**
 * AI Assistant JavaScript
 * Handles AI assistant interactions in ticket view
 */

var AIAssistant = (function() {
    'use strict';

    var ticketId = null;
    var isProcessing = false;

    /**
     * Initialize AI Assistant
     */
    function init() {
        var panel = document.getElementById('ai-assistant-panel');
        if (!panel) return;

        ticketId = panel.getAttribute('data-ticket-id');

        // Check if AI assistant is available
        checkAIStatus();

        // Setup keyboard shortcuts
        setupKeyboardShortcuts();
    }

    /**
     * Check AI assistant status
     */
    function checkAIStatus() {
        $.ajax({
            url: 'ajax.php/ai/status',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (!response.success || !response.enabled) {
                    showStatusMessage('AI Assistant is not enabled.', 'warning');
                    disableAIPanel();
                } else if (!response.canUse) {
                    showStatusMessage('You have reached the rate limit. Please try again later.', 'warning');
                    disableAIPanel();
                }
            },
            error: function() {
                showStatusMessage('Failed to check AI Assistant status.', 'error');
            }
        });
    }

    /**
     * Toggle AI assistant panel
     */
    function togglePanel() {
        var content = document.getElementById('ai-assistant-content');
        var icon = document.getElementById('ai-toggle-icon');

        if (content.style.display === 'none') {
            content.style.display = 'block';
            icon.className = 'icon-chevron-up toggle-icon';
        } else {
            content.style.display = 'none';
            icon.className = 'icon-chevron-down toggle-icon';
        }
    }

    /**
     * Set question from example button
     */
    function setQuestion(button) {
        var question = button.getAttribute('data-question');
        document.getElementById('ai-question').value = question;
    }

    /**
     * Ask AI assistant
     */
    function ask() {
        if (isProcessing) return;

        var questionInput = document.getElementById('ai-question');
        var question = questionInput.value.trim();

        if (!question) {
            showStatusMessage('Please enter a question.', 'error');
            return;
        }

        isProcessing = true;
        showLoading(true);
        hideStatusMessage();
        hideResponse();

        $.ajax({
            url: 'ajax.php/ai/tickets/' + ticketId + '/ask',
            type: 'POST',
            data: { question: question },
            dataType: 'json',
            success: function(response) {
                isProcessing = false;
                showLoading(false);

                if (response.success) {
                    showResponse(response.response, response.timestamp);
                    questionInput.value = ''; // Clear input
                } else {
                    showStatusMessage(response.error || 'Failed to get AI response.', 'error');
                }
            },
            error: function(xhr) {
                isProcessing = false;
                showLoading(false);

                var errorMsg = 'An error occurred while processing your request.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMsg = xhr.responseJSON.error;
                }
                showStatusMessage(errorMsg, 'error');
            }
        });
    }

    /**
     * Show AI response
     */
    function showResponse(response, timestamp) {
        var responseSection = document.getElementById('ai-response-section');
        var responseContent = document.getElementById('ai-response-content');
        var responseTimestamp = document.getElementById('ai-response-timestamp');

        responseContent.innerHTML = formatResponse(response);
        responseTimestamp.textContent = 'Generated at ' + (timestamp || new Date().toLocaleString());

        responseSection.style.display = 'block';
    }

    /**
     * Hide response section
     */
    function hideResponse() {
        var responseSection = document.getElementById('ai-response-section');
        responseSection.style.display = 'none';
    }

    /**
     * Clear AI response
     */
    function clearResponse() {
        hideResponse();
        document.getElementById('ai-response-content').innerHTML = '';
        document.getElementById('ai-response-timestamp').textContent = '';
    }

    /**
     * Copy AI response
     */
    function copyResponse() {
        var responseContent = document.getElementById('ai-response-content');
        var text = responseContent.innerText || responseContent.textContent;

        // Create temporary textarea
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();

        try {
            document.execCommand('copy');
            showStatusMessage('Response copied to clipboard!', 'success');
        } catch (err) {
            showStatusMessage('Failed to copy response.', 'error');
        }

        document.body.removeChild(textarea);
    }

    /**
     * Show loading indicator
     */
    function showLoading(show) {
        var loading = document.getElementById('ai-loading');
        var submitBtn = document.getElementById('ai-submit-btn');

        if (show) {
            loading.style.display = 'block';
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="icon-spinner icon-spin"></i> Processing...';
        } else {
            loading.style.display = 'none';
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="icon-magic"></i> Ask AI Assistant';
        }
    }

    /**
     * Show status message
     */
    function showStatusMessage(message, type) {
        var statusMsg = document.getElementById('ai-status-message');
        statusMsg.textContent = message;
        statusMsg.className = 'ai-status-message ai-status-' + type;
        statusMsg.style.display = 'block';

        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(function() {
                hideStatusMessage();
            }, 5000);
        }
    }

    /**
     * Hide status message
     */
    function hideStatusMessage() {
        var statusMsg = document.getElementById('ai-status-message');
        statusMsg.style.display = 'none';
    }

    /**
     * Disable AI panel
     */
    function disableAIPanel() {
        var submitBtn = document.getElementById('ai-submit-btn');
        var questionInput = document.getElementById('ai-question');

        if (submitBtn) submitBtn.disabled = true;
        if (questionInput) questionInput.disabled = true;
    }

    /**
     * Format AI response for display
     */
    function formatResponse(response) {
        // Escape HTML
        response = escapeHtml(response);

        // Convert markdown-like formatting
        response = response.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        response = response.replace(/\*(.*?)\*/g, '<em>$1</em>');
        response = response.replace(/\n\n/g, '</p><p>');
        response = response.replace(/\n/g, '<br>');

        return '<p>' + response + '</p>';
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Setup keyboard shortcuts
     */
    function setupKeyboardShortcuts() {
        document.getElementById('ai-question').addEventListener('keydown', function(e) {
            // Ctrl+Enter or Cmd+Enter to submit
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 13) {
                e.preventDefault();
                ask();
            }
        });
    }

    // Public API
    return {
        init: init,
        togglePanel: togglePanel,
        setQuestion: setQuestion,
        ask: ask,
        clearResponse: clearResponse,
        copyResponse: copyResponse
    };
})();

// Global functions for template onclick handlers
function initAIAssistant() {
    AIAssistant.init();
}

function toggleAIAssistant() {
    AIAssistant.togglePanel();
}

function setAIQuestion(button) {
    AIAssistant.setQuestion(button);
}

function askAIAssistant() {
    AIAssistant.ask();
}

function clearAIResponse() {
    AIAssistant.clearResponse();
}

function copyAIResponse() {
    AIAssistant.copyResponse();
}

// Initialize when document is ready
$(document).ready(function() {
    initAIAssistant();
});
