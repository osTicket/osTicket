using Newtonsoft.Json;
using System.Net;
using RestSharp;

namespace osTicketWrapper.ApiRequest
{
    public static class ApiRequest<TObject>
    {
        /// <summary>
        /// Executes a command in the desired API.
        /// </summary>
        /// <typeparam name="TObjectSend">Object that will be serialized to Json.</typeparam>
        /// <param name="Request">Request info.</param>
        /// <returns>RequestResult</returns>
        public static RequestResult Command<TObjectSend>(Request<TObjectSend> Request)
        {
            #region Create Request

            #region Information

            while (Request.Function.EndsWith("/"))
            {
                Request.Function = Request.Function.Remove(Request.Function.Length - 1);
            }

            var client = new RestClient(Request.URL)
            {
                UserAgent = Identity.Name + " " + Identity.Version,
                Timeout = 15000,
            };

            var restRequest = new RestRequest(Request.Method);
            var restResponse = default(IRestResponse);

            restRequest.Resource = Request.Function;

            if (Request.HeaderParameters != null && Request.HeaderParameters.Count > 0)
            {
                foreach (var Parameter in Request.HeaderParameters)
                {
                    restRequest.AddHeader(Parameter.Key, Parameter.Value);
                }
            }

            #endregion Information

            #region Json

            restRequest.AddHeader("Content-Type", "application/json");
            restRequest.RequestFormat = DataFormat.Json;

            if (Request.Method == Method.POST || Request.Method == Method.PUT)
            {
                restRequest.AddParameter("application/json", JsonConvert.SerializeObject(Request.Object), ParameterType.RequestBody);
            }

            #endregion Json

            #endregion Create Request

            #region Send Request

            RequestResult RequestResult = null;

            try
            {
                restResponse = client.Execute(restRequest);
            }
            finally
            {
                RequestResult = new RequestResult()
                {
                    StatusCode = restResponse != null ? restResponse.StatusCode : HttpStatusCode.NotFound,
                    ErrorMessage = restResponse != null ? restResponse.ErrorMessage : null,
                    Content = restResponse != null ? restResponse.Content : string.Empty,
                    Function = Request.Function,
                    Method = Request.Method,
                    Request = restRequest,
                    URL = Request.URL,
                };
            }

            return RequestResult;

            #endregion Send Request
        }
    }
}