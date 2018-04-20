/**
 *      SALE TYPES
 * 
 *      None = 0,
        EstateSales = 1,
        Auctions = 2,
        MovingSales = 4,
        BusinessLiquidations = 8,
        MovedOffsiteToWarehouse = 16,
        ByAppointment = 32,
        OnlineOnlyAuctions = 64,
        AuctionHouse = 128,
        MovedOffsiteToStore = 256,
        CharitySales = 512,
        OutsideSales = 1024,
        SingleItemTypeCollections = 2048,
        BuyoutsOrCleanouts = 4096,
        DemolitionSales = 8192
 */

private bool NeedNewAccessToken()
{
    return string.IsNullOrEmpty(this.accessToken) || this.accessTokenGoodUntil.HasValue == false || this.accessTokenGoodUntil.Value < DateTime.UtcNow.AddMinutes(-1);
}




private void SetNewAccessToken()
{
    RestRequest tokenRequest = new RestRequest("/token", Method.POST);
    tokenRequest.AddParameter("grant_type", "refresh_token");
    tokenRequest.AddParameter("refresh_token", this.apiKey);

    IRestResponse<AccessTokenResponse> response = this.restClient.Post<AccessTokenResponse>(tokenRequest);

    this.accessToken = response.Data.AccessToken;
    this.accessTokenGoodUntil = DateTime.UtcNow.AddSeconds(response.Data.ExpiresIn);
}




private RestRequest CreateRestRequest(string url, Method method, object jsonObject = null)
{
    if (this.NeedNewAccessToken())
    {
        this.SetNewAccessToken();
    }

    RestRequest request = new RestRequest(url, method);
    request.AddHeader("Authorization", $"Bearer {this.accessToken}");
    request.AddHeader("X_XSRF", "X_XSRF");

    if (jsonObject != null)
    {
        request.AddJsonBody(jsonObject);
    }

    return request;
}



public HttpStatusCode DeleteSale(int saleId)
{
    RestRequest request = this.CreateRestRequest($"/api/public-sales/{saleId}", Method.DELETE);
    IRestResponse response = this.restClient.Delete(request);

    response.VaildateResponse();

    return response.StatusCode;
}



public Sale UnpublishSale(int saleId)
{
    RestRequest request = this.CreateRestRequest($"/api/public-sales/{saleId}/unpublish", Method.POST);
    IRestResponse<Sale> response = this.restClient.Post<Sale>(request);

    response.VaildateResponse();

    return response.Data;
}



/// <summary>
/// This call will publish the sale onto the EstateSales.NET website for public viewing.
/// </summary>
/// <param name="saleId">The Id of the sale to be published</param>
/// <param name="autoPayAnyBalance">If this is set to false and there is a balance on the sale, it will
/// not be published. If it is set to true, the balance will be paid with the organization's credit card
/// on file. If the payment fails, the sale will not be published.</param>
public Sale PublishSale(int saleId, bool autoPayAnyBalance)
{
    RestRequest request = this.CreateRestRequest($"/api/public-sales/{saleId}/publish/{autoPayAnyBalance}", Method.POST);
    IRestResponse<Sale> response = this.restClient.Post<Sale>(request);

    response.VaildateResponse();

    return response.Data;
}



public Sale UpdateSale(Sale sale)
{
    if (sale == null)
    {
        throw new ArgumentNullException(nameof(sale));
    }

    if (sale.Id <= 0)
    {
        throw new Exception("Sale object must contain an Id in order to update");
    }

    RestRequest request = this.CreateRestRequest($"/api/public-sales/{sale.Id}", Method.PUT, sale);
    IRestResponse<Sale> response = this.restClient.Put<Sale>(request);

    response.VaildateResponse();

    return response.Data;
}


/**
 * data to post
 * - sale id
 * - orgid
 * - 
 * - data start day and time, end day and time
 * - address
 * - title
 * - terms & conditions
 * - description
 * - images
 */
public Sale CreateSale(Sale sale)
{
    RestRequest request = this.CreateRestRequest("/api/public-sales/", Method.POST, sale);
    IRestResponse<Sale> response = this.restClient.Post<Sale>(request);

    response.VaildateResponse();

    return response.Data;
}


public Sale GetSale(int saleId)
{
    RestRequest request = this.CreateRestRequest($"/api/public-sales/{saleId}", Method.GET);
    IRestResponse<Sale> response = this.restClient.Get<Sale>(request);

    response.VaildateResponse();

    return response.Data;
}



public SalePicture CreateSalePicture(SalePicture salePicture)
{
    RestRequest request = this.CreateRestRequest("/api/sale-pictures/", Method.POST, salePicture);
    IRestResponse<SalePicture> response = this.restClient.Post<SalePicture>(request);

    response.VaildateResponse();

    return response.Data;
}