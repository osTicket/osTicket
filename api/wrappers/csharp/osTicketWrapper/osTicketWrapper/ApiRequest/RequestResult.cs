using RestSharp;
using System.Net;

namespace osTicketWrapper.ApiRequest
{
    public class RequestResult
    {
        public HttpStatusCode StatusCode { get; set; }
        public string ErrorMessage { get; set; }
        public RestRequest Request { get; set; }
        public string Function { get; set; }
        public string Content { get; set; }
        public Method Method { get; set; }
        public string URL { get; set; }
    }
}