namespace osTicketWrapper.Models
{
    public class TicketRequest
    {
        public TicketRequest()
        {
            type = "text/plain";
            autorespond = true;
            source = "API";
            alert = true;
        }

        /// <summary>
        /// If unset, disable alerts to staff. Default is true.
        /// </summary>
        public bool alert { get; set; }

        /// <summary>
        /// If unset, disable autoresponses. Default is true.
        /// </summary>
        public bool autorespond { get; set; }

        /// <summary>
        /// Source of the ticket, default is API.
        /// Padrão: API
        /// </summary>
        public string source { get; set; }

        /// <summary>
        /// [required] Name of the submitter.
        /// </summary>
        public string name { get; set; }

        /// <summary>
        /// [required] Email address of the submitter.
        /// </summary>
        public string email { get; set; }

        /// <summary>
        /// [required] Subject of the ticket.
        /// </summary>
        public string subject { get; set; }

        /// <summary>
        /// IP address of the submitter.
        /// </summary>
        public string ip { get; set; }

        /// <summary>
        /// [required] Initial message for the ticket thread. The message content can be specified using RFC 2397 in the JSON format.
        /// The content of the message element should be the message body. Encoding is assumed based on the encoding
        /// attributed set in the xml processing instruction.
        /// </summary>
        public string message { get; set; }

        /// <summary>
        /// Content-Type of the message body. Valid values are text/html and text/plain. If not specified, text/plain is assumed.
        /// </summary>
        public string type { get; set; }
    }
}